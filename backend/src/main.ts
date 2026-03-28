import { NestFactory } from '@nestjs/core';
import { ValidationPipe, Logger } from '@nestjs/common';
import { DocumentBuilder, SwaggerModule } from '@nestjs/swagger';
import { AppModule } from './app.module';
import { TransformInterceptor } from './common/interceptors';
import { AllExceptionsFilter } from './common/filters';

async function bootstrap() {
  const logger = new Logger('Bootstrap');

  const app = await NestFactory.create(AppModule, {
    rawBody: true, // Needed for Stripe webhooks
  });

  // Global validation pipe
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      forbidNonWhitelisted: true,
      transform: true,
      transformOptions: {
        enableImplicitConversion: true,
      },
    }),
  );

  // Global interceptor for response formatting
  app.useGlobalInterceptors(new TransformInterceptor());

  // Global exception filter
  app.useGlobalFilters(new AllExceptionsFilter());

  // CORS
  app.enableCors({
    origin: process.env.CORS_ORIGIN || 'http://localhost:5173',
    methods: 'GET,HEAD,PUT,PATCH,POST,DELETE',
    credentials: true,
  });

  // Swagger API Documentation
  const config = new DocumentBuilder()
    .setTitle('T-Shirts Lab API')
    .setDescription('API documentation for T-Shirts Lab e-commerce platform')
    .setVersion('1.0.0')
    .addBearerAuth()
    .addTag('Auth', 'Authentication & Authorization')
    .addTag('Users', 'User management')
    .addTag('Products', 'Product catalog')
    .addTag('Orders', 'Order management')
    .addTag('Payments', 'Payment processing (Stripe)')
    .addTag('Health', 'Health check')
    .build();

  const document = SwaggerModule.createDocument(app, config);
  SwaggerModule.setup('api-docs', app, document);

  const port = process.env.PORT || 3000;
  await app.listen(port);

  logger.log(`🚀 T-Shirts Lab Backend running on: http://localhost:${port}`);
  logger.log(`📚 Swagger docs: http://localhost:${port}/api-docs`);
}

void bootstrap();
