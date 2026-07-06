import countUrl from '../../../../assets/images/input-group-icons/count.svg';

export interface IconProps {
  className?: string;
  width?: number | string;
  height?: number | string;
  alt?: string;
}

function CountIcon({ className, width = 16, height = 16, alt = 'count' }: IconProps) {
  return (
    <img
      src={countUrl as unknown as string}
      className={className}
      width={width}
      height={height}
      alt={alt}
    />
  );
}

export default CountIcon;
