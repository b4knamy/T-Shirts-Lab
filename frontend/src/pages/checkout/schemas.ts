import { z } from 'zod';

export const checkoutSchema = z.object({
  shippingAddress: z.object({
    street: z.string().min(1, 'Street is required'),
    number: z.string().min(1, 'Number is required'),
    complement: z.string().optional(),
    neighborhood: z.string().min(1, 'Neighborhood is required'),
    city: z.string().min(1, 'City is required'),
    state: z.string().min(1, 'State is required'),
    zipCode: z.string().min(5, 'ZIP Code is required'),
    country: z.string().min(1, 'Country is required'),
  }),
  customerNotes: z.string().optional(),
});
