import { useState } from "react";
import type { Coupon } from "../../../../types";

export function useCouponView() {
  const [viewTarget, setViewTarget] = useState<Coupon | null>(null);

  const openView = (coupon: Coupon) => {
    setViewTarget(coupon);
  };

  const closeView = () => {
    setViewTarget(null);
  };

  return {
    viewTarget,
    openView,
    closeView,
  };
}
