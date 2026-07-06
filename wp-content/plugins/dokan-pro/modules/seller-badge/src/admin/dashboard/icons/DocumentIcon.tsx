import documentUrl from '../../../../assets/images/input-group-icons/document.svg';

export interface IconProps {
  className?: string;
  width?: number | string;
  height?: number | string;
  alt?: string;
}

function DocumentIcon({ className, width = 16, height = 16, alt = 'document' }: IconProps) {
  return (
    <img
      src={documentUrl as unknown as string}
      className={className}
      width={width}
      height={height}
      alt={alt}
    />
  );
}

export default DocumentIcon;
