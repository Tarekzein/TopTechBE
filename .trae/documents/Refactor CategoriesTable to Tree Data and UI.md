## Overview
- Switch `CategoriesTable` to use `categoriesTreeApiResponse` as the sole data source.
- Remove pagination/sorting and server-side query params tied to paginated lists.
- Enhance table rendering for hierarchical display with indentation, child counts, and expand/collapse controls.

## Data Source Changes
- Remove `useGetCategoriesQuery` and related imports/types.
- Use only `useGetCategoriesTreeQuery` and set `categories` to `categoriesTreeApiResponse?.data ?? []`.
- Swap loading conditions to `isTreeLoading`/`isTreeFetching`.

## Tree Utilities
- Add `flattenCategories(tree)` to support select-all and parent selection lists.
- Update `renderParentCategoryOptions` to use flattened categories and exclude current.
- Add local search function `filterTreeByQuery(tree, q)` wired to `debouncedSearch` and remove the page-reset effect.

## Selection Logic
- Keep per-row selection; update select-all to operate on the flattened visible list.

## UI/UX Changes
- Remove the "Parent" column and all sorting controls.
- Add child count badge next to category name.
- Add "Expand All" / "Collapse All" actions near the title.
- Improve indentation and keep animations for row transitions.

## Remove Unused Code
- State: `page`, `perPage`, `sortField`, `sortOrder`, `pagination`.
- Functions/UI: `handleSort`, pagination bar and controls.
- Imports: `GetCategoriesParams` and `ChevronUp`.

## Verification
- Tree loads, expand/collapse works (including global controls).
- Select-all works against the visible flattened list; bulk delete behaves.
- Parent select lists all categories except current when editing.
- No references remain to paginated response fields or sorting.

Proceeding to implement these changes in `src/components/categories/categories-table.tsx`. 