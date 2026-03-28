import { NestFactory } from '@nestjs/core';
import { AppModule } from '../app.module';
import { DataSource } from 'typeorm';
import { Category } from '../modules/products/entities/category.entity';
import { Product } from '../modules/products/entities/product.entity';
import { ProductImage } from '../modules/products/entities/product-image.entity';
import { User } from '../modules/users/entities/user.entity';
import * as bcrypt from 'bcryptjs';
import { UserRole, ProductStatus } from '../common/constants/enums';

async function seed() {
  const app = await NestFactory.createApplicationContext(AppModule);
  const dataSource = app.get(DataSource);

  console.log('🌱 Starting database seeding...');

  // ---- Users ----
  const userRepo = dataSource.getRepository(User);
  const existingAdmin = await userRepo.findOne({
    where: { email: 'admin@tshirtslab.com' },
  });

  if (!existingAdmin) {
    const hashedPassword = await bcrypt.hash('Admin@123', 10);
    await userRepo.save(
      userRepo.create({
        email: 'admin@tshirtslab.com',
        passwordHash: hashedPassword,
        firstName: 'Admin',
        lastName: 'User',
        role: UserRole.ADMIN,
        isActive: true,
        isEmailVerified: true,
      }),
    );
    console.log('✅ Admin user created (admin@tshirtslab.com / Admin@123)');
  } else {
    console.log('⏭️  Admin user already exists');
  }

  // ---- Categories ----
  const categoryRepo = dataSource.getRepository(Category);
  const categories = [
    { name: 'Men', slug: 'men', description: 'T-shirts for men', sortOrder: 1 },
    {
      name: 'Women',
      slug: 'women',
      description: 'T-shirts for women',
      sortOrder: 2,
    },
    {
      name: 'Kids',
      slug: 'kids',
      description: 'T-shirts for kids',
      sortOrder: 3,
    },
    {
      name: 'Unisex',
      slug: 'unisex',
      description: 'Unisex t-shirts',
      sortOrder: 4,
    },
    {
      name: 'Limited Edition',
      slug: 'limited-edition',
      description: 'Limited edition designs',
      sortOrder: 5,
    },
  ];

  const savedCategories: Category[] = [];
  for (const cat of categories) {
    let existing = await categoryRepo.findOne({ where: { slug: cat.slug } });
    if (!existing) {
      existing = await categoryRepo.save(
        categoryRepo.create({ ...cat, isActive: true }),
      );
      console.log(`✅ Category "${cat.name}" created`);
    } else {
      console.log(`⏭️  Category "${cat.name}" already exists`);
    }
    savedCategories.push(existing);
  }

  // ---- Products ----
  const productRepo = dataSource.getRepository(Product);
  const imageRepo = dataSource.getRepository(ProductImage);

  const products = [
    {
      sku: 'TSL-M-001',
      name: 'Classic Crew Neck Tee',
      slug: 'classic-crew-neck-tee',
      description:
        'A timeless classic crew neck t-shirt made from 100% organic cotton.',
      longDescription:
        'Experience premium comfort with our Classic Crew Neck Tee. Made from 100% organic cotton, this shirt features a relaxed fit that looks great on everyone. Perfect for casual outings or layering.',
      price: 29.99,
      costPrice: 12.0,
      stockQuantity: 150,
      categoryIndex: 0,
      isFeatured: true,
      color: 'White',
      size: 'M',
    },
    {
      sku: 'TSL-M-002',
      name: 'Urban Street Art Tee',
      slug: 'urban-street-art-tee',
      description: 'Bold street art-inspired design for the urban explorer.',
      price: 34.99,
      discountPrice: 27.99,
      discountPercent: 20,
      costPrice: 14.0,
      stockQuantity: 80,
      categoryIndex: 0,
      isFeatured: true,
      color: 'Black',
      size: 'L',
    },
    {
      sku: 'TSL-W-001',
      name: 'Floral Dream Tee',
      slug: 'floral-dream-tee',
      description: 'Beautiful floral patterns on a soft premium cotton blend.',
      price: 32.99,
      costPrice: 13.0,
      stockQuantity: 100,
      categoryIndex: 1,
      isFeatured: true,
      color: 'Pink',
      size: 'S',
    },
    {
      sku: 'TSL-W-002',
      name: 'Minimalist Wave Tee',
      slug: 'minimalist-wave-tee',
      description: 'Clean minimalist wave design. Less is more.',
      price: 28.99,
      costPrice: 11.0,
      stockQuantity: 120,
      categoryIndex: 1,
      isFeatured: false,
      color: 'Navy',
      size: 'M',
    },
    {
      sku: 'TSL-K-001',
      name: 'Dino Explorer Kids Tee',
      slug: 'dino-explorer-kids-tee',
      description:
        'Fun dinosaur design that kids love! Made with soft, durable fabric.',
      price: 19.99,
      costPrice: 8.0,
      stockQuantity: 200,
      categoryIndex: 2,
      isFeatured: true,
      color: 'Green',
      size: 'Kids M',
    },
    {
      sku: 'TSL-U-001',
      name: 'Vintage Sunset Tee',
      slug: 'vintage-sunset-tee',
      description:
        'Retro-inspired vintage sunset graphic for that nostalgic vibe.',
      price: 31.99,
      discountPrice: 24.99,
      discountPercent: 22,
      costPrice: 12.5,
      stockQuantity: 90,
      categoryIndex: 3,
      isFeatured: true,
      color: 'Orange',
      size: 'L',
    },
    {
      sku: 'TSL-LE-001',
      name: 'Artist Series: Cosmos',
      slug: 'artist-series-cosmos',
      description:
        'Limited edition cosmic-themed design by featured artist. Only 50 made.',
      longDescription:
        'Part of our exclusive Artist Series, this cosmic-themed t-shirt is a wearable masterpiece. Featuring a hand-drawn galaxy illustration printed on ultra-premium heavyweight cotton. Limited to only 50 units worldwide.',
      price: 49.99,
      costPrice: 20.0,
      stockQuantity: 50,
      categoryIndex: 4,
      isFeatured: true,
      color: 'Dark Blue',
      size: 'M',
    },
    {
      sku: 'TSL-M-003',
      name: 'Tech Geometric Tee',
      slug: 'tech-geometric-tee',
      description: 'Modern geometric patterns for the tech-savvy individual.',
      price: 33.99,
      costPrice: 13.5,
      stockQuantity: 75,
      categoryIndex: 0,
      isFeatured: false,
      color: 'Gray',
      size: 'XL',
    },
  ];

  for (const p of products) {
    const existing = await productRepo.findOne({ where: { sku: p.sku } });
    if (!existing) {
      const { categoryIndex, ...productData } = p;
      const product = await productRepo.save(
        productRepo.create({
          ...productData,
          category: savedCategories[categoryIndex],
          status: ProductStatus.ACTIVE,
          reservedQuantity: 0,
        }),
      );

      // Add a placeholder image
      await imageRepo.save(
        imageRepo.create({
          product,
          imageUrl: `https://placehold.co/600x600/f8f9fa/333?text=${encodeURIComponent(p.name)}`,
          altText: p.name,
          sortOrder: 0,
          isPrimary: true,
        }),
      );

      console.log(`✅ Product "${p.name}" created`);
    } else {
      console.log(`⏭️  Product "${p.name}" already exists`);
    }
  }

  console.log('\n🎉 Seeding completed!');
  await app.close();
}

seed().catch((error) => {
  console.error('❌ Seeding failed:', error);
  process.exit(1);
});
