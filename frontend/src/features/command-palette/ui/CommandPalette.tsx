import { useEffect, useMemo, useRef, useState, type KeyboardEvent } from 'react'
import { Icon } from '@/shared/ui'
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
 * naturally on each open (no reset effects).
 */
export function CommandPalette({ onClose, commands, labels }: CommandPaletteProps) {
  const [query, setQuery] = useState('')
  const [highlight, setHighlight] = useState(0)
  const inputRef = useRef<HTMLInputElement>(null)

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase()
    if (needle === '') return commands
    return commands.filter((command) => command.label.toLowerCase().includes(needle))
  }, [commands, query])

  useEffect(() => {
    inputRef.current?.focus()
  }, [])

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
        onClick={onClose}
      />
      <div className={styles['panel']} role="dialog" aria-modal aria-label={labels.title}>
        <div className={styles['field']}>
          <Icon name="search" size={20} color="var(--fg-3)" />
          <input
            ref={inputRef}
            type="text"
            className={styles['input']}
            placeholder={labels.placeholder}
            value={query}
            aria-label={labels.title}
            onChange={(event) => {
              setQuery(event.target.value)
              setHighlight(0)
            }}
            onKeyDown={onKeyDown}
          />
        </div>
        {filtered.length === 0 ? (
          <p className={styles['empty']}>{labels.empty}</p>
        ) : (
          <ul className={styles['list']} role="listbox" aria-label={labels.title}>
            {filtered.map((command, index) => {
              const isGroupStart = index === 0 || filtered[index - 1]?.group !== command.group
              return (
                <li key={command.id}>
                  {isGroupStart ? (
                    <span className={styles['group']}>{groupLabel(command.group)}</span>
                  ) : null}
                  <button
                    type="button"
                    role="option"
                    aria-selected={index === highlight}
                    data-active={index === highlight}
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
