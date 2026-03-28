import {
  Injectable,
  NotFoundException,
  BadRequestException,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { EventEmitter2 } from '@nestjs/event-emitter';
import { Order } from './entities/order.entity';
import { OrderItem } from './entities/order-item.entity';
import { CreateOrderDto, UpdateOrderStatusDto } from './dto';
import { ProductsService } from '../products/products.service';
import { OrderStatus, PaymentStatus } from '../../common/constants/enums';
import { v4 as uuidv4 } from 'uuid';

@Injectable()
export class OrdersService {
  constructor(
    @InjectRepository(Order)
    private readonly orderRepository: Repository<Order>,
    @InjectRepository(OrderItem)
    private readonly orderItemRepository: Repository<OrderItem>,
    private readonly productsService: ProductsService,
    private readonly eventEmitter: EventEmitter2,
  ) {}

  async create(userId: string, createOrderDto: CreateOrderDto): Promise<Order> {
    let subtotal = 0;
    const orderItems: Partial<OrderItem>[] = [];

    for (const item of createOrderDto.items) {
      const product = await this.productsService.findOne(item.productId);

      if (product.availableQuantity < item.quantity) {
        throw new BadRequestException(
          `Product "${product.name}" does not have enough stock. Available: ${product.availableQuantity}`,
        );
      }

      const itemTotal = Number(product.price) * item.quantity;
      subtotal += itemTotal;

      orderItems.push({
        productId: item.productId,
        designId: item.designId,
        quantity: item.quantity,
        unitPrice: Number(product.price),
      });
    }

    const taxAmount = subtotal * 0.1; // 10% tax
    const shippingCost = subtotal > 200 ? 0 : 15; // Free shipping over $200
    const total = subtotal + taxAmount + shippingCost;

    const orderNumber = `TSL-${Date.now()}-${uuidv4().slice(0, 4).toUpperCase()}`;

    const order = this.orderRepository.create({
      orderNumber,
      userId,
      subtotal,
      taxAmount,
      shippingCost,
      total,
      status: OrderStatus.PENDING,
      paymentStatus: PaymentStatus.PENDING,
      shippingAddressId: createOrderDto.shippingAddressId,
      billingAddressId: createOrderDto.billingAddressId,
      customerNotes: createOrderDto.customerNotes,
    });

    const savedOrder = await this.orderRepository.save(order);

    // Save order items
    for (const item of orderItems) {
      const orderItem = this.orderItemRepository.create({
        ...item,
        orderId: savedOrder.id,
      });
      await this.orderItemRepository.save(orderItem);
    }

    // Emit order created event
    this.eventEmitter.emit('order.created', { order: savedOrder });

    return this.findOne(savedOrder.id);
  }

  async findOne(id: string): Promise<Order> {
    const order = await this.orderRepository.findOne({
      where: { id },
      relations: ['items', 'items.product', 'user'],
    });

    if (!order) {
      throw new NotFoundException(`Order with ID ${id} not found`);
    }

    return order;
  }

  async findByUser(userId: string, page = 1, limit = 20) {
    const [orders, total] = await this.orderRepository.findAndCount({
      where: { userId },
      relations: ['items', 'items.product'],
      skip: (page - 1) * limit,
      take: limit,
      order: { createdAt: 'DESC' },
    });

    return { orders, total, page, limit };
  }

  async findAll(page = 1, limit = 20) {
    const [orders, total] = await this.orderRepository.findAndCount({
      relations: ['items', 'user'],
      skip: (page - 1) * limit,
      take: limit,
      order: { createdAt: 'DESC' },
    });

    return { orders, total, page, limit };
  }

  async updateStatus(id: string, dto: UpdateOrderStatusDto): Promise<Order> {
    const order = await this.findOne(id);
    order.status = dto.status as OrderStatus;
    if (dto.adminNotes) {
      order.adminNotes = dto.adminNotes;
    }

    const updatedOrder = await this.orderRepository.save(order);
    this.eventEmitter.emit('order.status.updated', {
      order: updatedOrder,
      previousStatus: order.status,
    });

    return updatedOrder;
  }

  async updatePaymentStatus(
    orderId: string,
    status: PaymentStatus,
  ): Promise<Order> {
    const order = await this.findOne(orderId);
    order.paymentStatus = status;

    if (status === PaymentStatus.COMPLETED) {
      order.status = OrderStatus.CONFIRMED;
    }

    return this.orderRepository.save(order);
  }
}
