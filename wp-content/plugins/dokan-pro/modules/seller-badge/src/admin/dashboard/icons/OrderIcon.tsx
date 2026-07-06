import orderUrl from '../../../../assets/images/input-group-icons/order.svg';

export interface IconProps {
  className?: string;
  width?: number | string;
  height?: number | string;
  alt?: string;
}

function OrderIcon({ className, width = 16, height = 16, alt = 'order' }: IconProps) {
  return (
    <img
      src={orderUrl as unknown as string}
      className={className}
      width={width}
      height={height}
      alt={alt}
    />
  );
}

export default OrderIcon;
