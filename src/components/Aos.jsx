/**
 * Thin wrapper around AOS data attributes. Init runs in Layout so animations
 * refresh on client-side navigation.
 */
export default function Aos({
  children,
  animation = 'fade-up',
  delay = 0,
  duration,
  anchor,
  offset,
  easing,
  once = true,
  className = '',
  as: Tag = 'div',
}) {
  return (
    <Tag
      className={className}
      data-aos={animation}
      data-aos-delay={delay || undefined}
      data-aos-duration={duration || undefined}
      data-aos-anchor={anchor || undefined}
      data-aos-offset={offset ?? undefined}
      data-aos-easing={easing || undefined}
      data-aos-once={once ? 'true' : 'false'}
    >
      {children}
    </Tag>
  );
}
