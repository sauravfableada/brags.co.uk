import reviewUrl from '../../../../assets/images/input-group-icons/review.svg';

export interface IconProps {
  className?: string;
  width?: number | string;
  height?: number | string;
  alt?: string;
}

function ReviewIcon({ className, width = 16, height = 16, alt = 'review' }: IconProps) {
  return (
    <img
      src={reviewUrl as unknown as string}
      className={className}
      width={width}
      height={height}
      alt={alt}
    />
  );
}

export default ReviewIcon;
