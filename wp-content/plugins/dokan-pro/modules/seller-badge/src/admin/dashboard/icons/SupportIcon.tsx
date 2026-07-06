import supportUrl from '../../../../assets/images/input-group-icons/support.svg';

export interface IconProps {
  className?: string;
  width?: number | string;
  height?: number | string;
  alt?: string;
}

function SupportIcon({ className, width = 16, height = 16, alt = 'support' }: IconProps) {
  return (
    <img
      src={supportUrl as unknown as string}
      className={className}
      width={width}
      height={height}
      alt={alt}
    />
  );
}

export default SupportIcon;
