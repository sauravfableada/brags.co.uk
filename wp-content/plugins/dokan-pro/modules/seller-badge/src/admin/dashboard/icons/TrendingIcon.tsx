import trendingUrl from '../../../../assets/images/input-group-icons/trending.svg';

export interface IconProps {
  className?: string;
  width?: number | string;
  height?: number | string;
  alt?: string;
}

function TrendingIcon({ className, width = 16, height = 16, alt = 'trending' }: IconProps) {
  return (
    <img
      src={trendingUrl as unknown as string}
      className={className}
      width={width}
      height={height}
      alt={alt}
    />
  );
}

export default TrendingIcon;
