import Link from '@docusaurus/Link';
import styles from './styles.module.css';
import {JSX} from 'react';

type Props = {
  name: string;
  className: string;
  description: string;
  href: string;
  category?: 'style' | 'naming' | 'structure';
};

const categoryColors: Record<string, string> = {
  style: '#2563eb',
  naming: '#16a34a',
  structure: '#d97706',
};

export default function SniffCard({
  name,
  className,
  description,
  href,
  category = 'style',
}: Props): JSX.Element {
  return (
    <Link to={href} className={styles.card}>
      <div className={styles.header}>
        <h3 className={styles.name}>{name}</h3>
        <span
          className={styles.badge}
          style={{borderColor: categoryColors[category]}}>
          {category}
        </span>
      </div>
      <code className={styles.className}>{className}</code>
      <p className={styles.description}>{description}</p>
      <span className={styles.arrow}>Read more →</span>
    </Link>
  );
}
