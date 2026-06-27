import {
  useOrganizations,
  useCreateOrganization,
  useRenameOrganization,
  useDisableOrganization,
  type Organization,
  type RenameOrganizationInput,
} from '@/entities/organization'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface CreateOrganizationFields {
  name: string
  slug: string
}

export interface UseOrganizationConsoleResult {
  organizations: Organization[]
  isLoading: boolean
  isError: boolean
  createOrganization: (
    fields: CreateOrganizationFields,
    options?: { onSuccess?: () => void },
  ) => void
  renameOrganization: (input: RenameOrganizationInput) => void
  disableOrganization: (id: string) => void
  isCreating: boolean
  isRenaming: boolean
  isDisabling: boolean
  createErrorKey: MessageKey | null
}

/** Glue between the organization entity hooks and the console UI (no JSX). */
export function useOrganizationConsole(): UseOrganizationConsoleResult {
  const query = useOrganizations()
  const create = useCreateOrganization()
  const rename = useRenameOrganization()
  const disable = useDisableOrganization()

  return {
    organizations: query.data ?? [],
    isLoading: query.isLoading,
    isError: query.isError,
    createOrganization: (fields, options) => {
      create.mutate(fields, options)
    },
    renameOrganization: (input) => {
      rename.mutate(input)
    },
    disableOrganization: (id) => {
      disable.mutate(id)
    },
    isCreating: create.isPending,
    isRenaming: rename.isPending,
    isDisabling: disable.isPending,
    createErrorKey: create.error !== null ? mapProblemDetailsToMessageKey(create.error) : null,
  }
}
