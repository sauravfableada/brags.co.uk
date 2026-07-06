import compareUrl from '../../../../assets/images/input-group-icons/compare.svg';

export interface IconProps {
    className?: string;
    width?: number | string;
    height?: number | string;
    alt?: string;
}

function CompareIcon( {
    className,
    width = 16,
    height = 16,
    alt = 'compare',
}: IconProps ) {
    return (
        <img
            src={ compareUrl as unknown as string }
            className={ className }
            width={ width }
            height={ height }
            alt={ alt }
        />
    );
}

export default CompareIcon;
