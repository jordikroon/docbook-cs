import styles from './styles.module.css';
import {JSX} from 'react';

type Property = {
  name: string;
  type: string;
  default?: string;
  description: string;
};

type Props = {
  properties: Property[];
};

export default function PropertyTable({properties}: Props): JSX.Element {
  return (
    <div className={styles.wrapper}>
      <table className={styles.table}>
        <thead>
        <tr>
          <th>Property</th>
          <th>Type</th>
          <th>Default</th>
          <th>Description</th>
        </tr>
        </thead>
        <tbody>
        {properties.map((p) => (
          <tr key={p.name}>
            <td><code>{p.name}</code></td>
            <td><code className={styles.type}>{p.type}</code></td>
            <td>{p.default ? <code>{p.default}</code> : <span className={styles.muted}>—</span>}</td>
            <td>{p.description}</td>
          </tr>
        ))}
        </tbody>
      </table>
    </div>
  );
}
