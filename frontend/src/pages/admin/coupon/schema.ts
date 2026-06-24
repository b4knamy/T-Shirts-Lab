import z from "zod";

export const couponSchema = z.object({
  code: z.string().min(1, "Code is required"),
  type: z.enum(["PERCENTAGE", "FIXED"]),
  value: z.string().min(1, "Value is required"),
  min_order_amount: z.string().optional(),
  max_discount_amount: z.string().optional(),
  usage_limit: z.string().optional(),
  per_user_limit: z.string().optional(),
  description: z.string().optional(),
  starts_at: z.string().optional(),
  expires_at: z.string().optional(),
  is_active: z.boolean().optional(),
  is_public: z.boolean().optional(),
});
