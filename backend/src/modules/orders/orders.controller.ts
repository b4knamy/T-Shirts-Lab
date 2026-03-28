import {
  Controller,
  Get,
  Post,
  Patch,
  Body,
  Param,
  Query,
  UseGuards,
  ParseUUIDPipe,
} from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { OrdersService } from './orders.service';
import { CreateOrderDto, UpdateOrderStatusDto } from './dto';
import { CurrentUser, Roles } from '../../common/decorators';
import { RolesGuard } from '../../common/guards';
import { UserRole } from '../../common/constants/enums';

@ApiTags('Orders')
@Controller('api/v1/orders')
@UseGuards(AuthGuard('jwt'))
@ApiBearerAuth()
export class OrdersController {
  constructor(private readonly ordersService: OrdersService) {}

  @Post()
  @ApiOperation({ summary: 'Create a new order' })
  async create(
    @CurrentUser('userId') userId: string,
    @Body() createOrderDto: CreateOrderDto,
  ) {
    return this.ordersService.create(userId, createOrderDto);
  }

  @Get('my-orders')
  @ApiOperation({ summary: 'Get current user orders' })
  async getMyOrders(
    @CurrentUser('userId') userId: string,
    @Query('page') page?: number,
    @Query('limit') limit?: number,
  ) {
    return this.ordersService.findByUser(userId, page, limit);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get order by ID' })
  async findOne(@Param('id', ParseUUIDPipe) id: string) {
    return this.ordersService.findOne(id);
  }

  @Get()
  @UseGuards(RolesGuard)
  @Roles(UserRole.ADMIN, UserRole.SUPER_ADMIN)
  @ApiOperation({ summary: 'List all orders (Admin only)' })
  async findAll(@Query('page') page?: number, @Query('limit') limit?: number) {
    return this.ordersService.findAll(page, limit);
  }

  @Patch(':id/status')
  @UseGuards(RolesGuard)
  @Roles(UserRole.ADMIN, UserRole.SUPER_ADMIN)
  @ApiOperation({ summary: 'Update order status (Admin only)' })
  async updateStatus(
    @Param('id', ParseUUIDPipe) id: string,
    @Body() updateStatusDto: UpdateOrderStatusDto,
  ) {
    return this.ordersService.updateStatus(id, updateStatusDto);
  }
}
