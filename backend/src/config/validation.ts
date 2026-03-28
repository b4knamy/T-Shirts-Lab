import { plainToInstance } from 'class-transformer';
import { IsEnum, IsNumber, IsString, validateSync } from 'class-validator';

enum Environment {
  Development = 'development',
  Production = 'production',
  Test = 'test',
}

class EnvironmentVariables {
  @IsEnum(Environment)
  NODE_ENV: Environment = Environment.Development;

  @IsNumber()
  PORT = 3000;

  @IsString()
  DATABASE_HOST!: string;

  @IsNumber()
  DATABASE_PORT = 5432;

  @IsString()
  DATABASE_USER!: string;

  @IsString()
  DATABASE_PASSWORD!: string;

  @IsString()
  DATABASE_NAME!: string;

  @IsString()
  REDIS_HOST!: string;

  @IsNumber()
  REDIS_PORT = 6379;

  @IsString()
  JWT_SECRET!: string;

  @IsString()
  JWT_EXPIRATION = '15m';

  @IsString()
  JWT_REFRESH_SECRET!: string;

  @IsString()
  JWT_REFRESH_EXPIRATION = '7d';

  @IsString()
  STRIPE_SECRET_KEY!: string;

  @IsString()
  STRIPE_WEBHOOK_SECRET!: string;

  @IsString()
  CORS_ORIGIN = 'http://localhost:5173';
}

export function validate(config: Record<string, unknown>) {
  const validatedConfig = plainToInstance(EnvironmentVariables, config, {
    enableImplicitConversion: true,
  });

  const errors = validateSync(validatedConfig, {
    skipMissingProperties: false,
  });

  if (errors.length > 0) {
    throw new Error(errors.toString());
  }

  return validatedConfig;
}
