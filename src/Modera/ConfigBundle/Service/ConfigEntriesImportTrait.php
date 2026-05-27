<?php

namespace Modera\ConfigBundle\Service;

use Modera\ConfigBundle\Entity\ConfigurationEntry;

trait ConfigEntriesImportTrait
{
    private function importConfigEntries(array $items): void
    {
        $repo = $this->em->getRepository(ConfigurationEntry::class);

        foreach ($items as $item) {
            if (!isset($item['name']) || !array_key_exists('value', $item)) {
                continue;
            }

            $entry = $repo->findOneBy(['name' => $item['name']]);
            if (!$entry) {
                continue;
            }

            $entry->setDenormalizedValue($item['value']);
        }
    }
}