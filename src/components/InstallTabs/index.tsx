import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';
import CodeBlock from '@theme/CodeBlock';
import {JSX} from 'react';

type Props = {
  /** Composer package name */
  pkg?: string;
};

export default function InstallTabs({
  pkg = 'jordikroon/docbook-cs',
}: Props): JSX.Element {
  return (
    <Tabs groupId="install-method" queryString>
      <TabItem value="composer" label="Composer" default>
        <CodeBlock language="bash">{`composer require --dev ${pkg}`}</CodeBlock>
      </TabItem>
    </Tabs>
  );
}
