import timingUrl from '../../../../assets/images/input-group-icons/timing.svg';

export interface IconProps {
  className?: string;
  width?: number | string;
  height?: number | string;
  alt?: string;
}

function TimingIcon({ className, width = 16, height = 16, alt = 'timing' }: IconProps) {
  return (
    <img
      src={timingUrl as unknown as string}
      className={className}
      width={width}
      height={height}
      alt={alt}
    />
  );
}

export default TimingIcon;
