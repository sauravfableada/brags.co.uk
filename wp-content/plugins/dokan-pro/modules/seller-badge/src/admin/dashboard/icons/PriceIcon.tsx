import priceUrl from '../../../../assets/images/input-group-icons/price.svg';

export interface IconProps {
  className?: string;
  width?: number | string;
  height?: number | string;
  alt?: string;
}

function PriceIcon({ className, width = 16, height = 16, alt = 'price' }: IconProps) {
  return (
    <img
      src={priceUrl as unknown as string}
      className={className}
      width={width}
      height={height}
      alt={alt}
    />
  );
}

export default PriceIcon;
