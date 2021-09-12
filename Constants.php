<?php declare(strict_types=1);

/**
 * Константы используетые в пакете mrcore.
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore
 */

const MRCORE_LINE_DOUBLE = "================================================================================\n",
      MRCORE_LINE_DOT    = "................................................................................\n",
      MRCORE_LINE_DASH   = "--------------------------------------------------------------------------------\n";

// стандартные статусы записей БД (:TODO: объединить с админкой)
const MRCORE_ENTRY_STATUS_DRAFT    = 'draft',
      MRCORE_ENTRY_STATUS_ENABLED  = 'enabled',
      MRCORE_ENTRY_STATUS_HIDDEN   = 'hidden',
      MRCORE_ENTRY_STATUS_ARCHIVED = 'archived',
      MRCORE_ENTRY_STATUS_DISABLED = 'disabled',
      MRCORE_ENTRY_STATUS_SYSTEM   = 'system',
      MRCORE_ENTRY_STATUS_REMOVED  = 'removed';