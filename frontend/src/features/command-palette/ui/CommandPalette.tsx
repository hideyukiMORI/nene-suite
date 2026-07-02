import { useId, useMemo, useRef, useState, type KeyboardEvent } from 'react'
import { Icon, useFocusTrap } from '@/shared/ui'
import type { Command } from '../command'
import styles from './CommandPalette.module.css'

interface CommandPaletteLabels {
  title: string
  placeholder: string
  empty: string
  navGroup: string
  actionGroup: string
}

interface CommandPaletteProps {
  onClose: () => void
  commands: Command[]
  labels: CommandPaletteLabels
}

/**
 * ⌘K command palette (DESIGN-SYSTEM.md §5.5 popover). Presentational + keyboard
 * logic over a caller-supplied command list — the shell owns what the commands
 * do (navigation, theme). Mounted only while open, so query/highlight reset
 * naturally on each open (no reset effects). Keyboard follows the WAI-ARIA APG
 * combobox pattern: focus stays on the input, the highlighted option is exposed
 * through aria-activedescendant, and focus returns to the trigger on close.
 */
export function CommandPalette({ onClose, commands, labels }: CommandPaletteProps) {
  const [query, setQuery] = useState('')
  const [highlight, setHighlight] = useState(0)
  const inputRef = useRef<HTMLInputElement>(null)
  const panelRef = useRef<HTMLDivElement>(null)
  const listboxId = useId()

  useFocusTrap(panelRef, inputRef)

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (needle === '') return commands
    return commands.filter((command) => command.label.toLowerCase().includes(needle))
  }, [commands, query])

  const optionId = (index: number): string => `${listboxId}-option-${String(index)}`

  const groupLabel = (group: Command['group']): string =>
    group === 'nav' ? labels.navGroup : labels.actionGroup

  const runCommand = (command: Command): void => {
    onClose()
    command.run()
  }

  const onKeyDown = (event: KeyboardEvent<HTMLInputElement>): void => {
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      setHighlight((current) => Math.min(current + 1, filtered.length - 1))
    } else if (event.key === 'ArrowUp') {
      event.preventDefault()
      setHighlight((current) => Math.max(current - 1, 0))
    } else if (event.key === 'Enter') {
      event.preventDefault()
      const command = filtered[highlight]
      if (command !== undefined) runCommand(command)
    } else if (event.key === 'Escape') {
      event.preventDefault()
      onClose()
    }
  }

  return (
    <div className={styles['overlay']}>
      <button
        type="button"
        className={styles['scrim']}
        aria-label={labels.title}
        tabIndex={-1}
        onClick={onClose}
      />
      <div
        ref={panelRef}
        className={styles['panel']}
        role="dialog"
        aria-modal
        aria-label={labels.title}
      >
        <div className={styles['field']}>
          <Icon name="search" size={20} color="var(--fg-3)" />
          <input
            ref={inputRef}
            type="text"
            className={styles['input']}
            placeholder={labels.placeholder}
            value={query}
            role="combobox"
            aria-label={labels.title}
            aria-expanded={filtered.length > 0}
            aria-controls={listboxId}
            aria-autocomplete="list"
            aria-activedescendant={filtered.length > 0 ? optionId(highlight) : undefined}
            onChange={(event) => {
              setQuery(event.target.value)
              setHighlight(0)
            }}
            onKeyDown={onKeyDown}
          />
        </div>
        {filtered.length === 0 ? (
          <p className={styles['empty']} role="status">
            {labels.empty}
          </p>
        ) : (
          <ul id={listboxId} className={styles['list']} role="listbox" aria-label={labels.title}>
            {filtered.map((command, index) => {
              const isGroupStart = index === 0 || filtered[index - 1]?.group !== command.group
              return (
                <li key={command.id} role="presentation">
                  {isGroupStart ? (
                    <span className={styles['group']}>{groupLabel(command.group)}</span>
                  ) : null}
                  <button
                    type="button"
                    id={optionId(index)}
                    role="option"
                    aria-selected={index === highlight}
                    data-active={index === highlight}
                    tabIndex={-1}
                    className={styles['item']}
                    onMouseEnter={() => {
                      setHighlight(index)
                    }}
                    onClick={() => {
                      runCommand(command)
                    }}
                  >
                    <Icon name={command.icon} size={19} color="var(--fg-3)" />
                    <span>{command.label}</span>
                  </button>
                </li>
              )
            })}
          </ul>
        )}
      </div>
    </div>
  )
}
