/** A single command-palette entry. The shell supplies these (it owns routing). */
export interface Command {
  id: string
  group: 'nav' | 'action'
  label: string
  /** Material Symbols ligature name. */
  icon: string
  run: () => void
}
