/**
 * The ui kit: Vue ports of the Sheaf / Preline Blade components from stephenson-research.
 *
 *   import { Button, Input, Field, Label, FieldError } from '@/components/ui';
 *
 * Blade → Vue naming: <x-ui.select.option> is <SelectOption>, <x-ui.alerts.heading> is <AlertHeading>,
 * <x-ui.table.th> is <TableHeader>, and so on.
 */

// Primitives
export { default as Icon } from './icon/Icon.vue';
export { default as IconLoading } from './icon/IconLoading.vue';
export { default as Button } from './button/Button.vue';
export { default as Badge } from './badge/Badge.vue';
export { default as Heading } from './typography/Heading.vue';
export { default as Text } from './typography/Text.vue';
export { default as TextLink } from './typography/TextLink.vue';
export { default as Kbd } from './typography/Kbd.vue';

// Forms
export { default as Field } from './form/Field.vue';
export { default as Label } from './form/Label.vue';
export { default as Description } from './form/Description.vue';
export { default as FieldError } from './form/FieldError.vue';
export { default as Fieldset } from './form/Fieldset.vue';
export { default as ValidationSummary } from './form/ValidationSummary.vue';
export { default as Input } from './input/Input.vue';
export { default as Textarea } from './textarea/Textarea.vue';
export { default as TextField } from './text-field/TextField.vue';
export { default as Checkbox } from './checkbox/Checkbox.vue';
export { default as CheckboxGroup } from './checkbox/CheckboxGroup.vue';
export { default as NativeCheckbox } from './checkbox/NativeCheckbox.vue';
export { default as RadioGroup } from './radio/RadioGroup.vue';
export { default as RadioItem } from './radio/RadioItem.vue';
export { default as Switch } from './switch/Switch.vue';
export { default as Segmented } from './segmented/Segmented.vue';

// Select & combobox (they share their option components)
export { default as Select } from './select/Select.vue';
export { default as Combobox } from './combobox/Combobox.vue';
export {
    default as SelectOption,
    default as ComboboxOption,
} from './listbox/ListboxOption.vue';
export {
    default as SelectGroup,
    default as ComboboxGroup,
} from './listbox/ListboxGroup.vue';
export {
    default as SelectSeparator,
    default as ComboboxSeparator,
} from './listbox/ListboxSeparator.vue';
export {
    default as SelectEmpty,
    default as ComboboxEmpty,
} from './listbox/ListboxEmpty.vue';
export {
    default as SelectCreateOption,
    default as ComboboxCreateOption,
} from './listbox/ListboxCreateOption.vue';

// Layout & display
export { default as Card } from './card/Card.vue';
export { default as Container } from './card/Container.vue';
export { default as Avatar } from './avatar/Avatar.vue';
export { default as AvatarGroup } from './avatar/AvatarGroup.vue';
export { default as Alert } from './alert/Alert.vue';
export { default as AlertHeading } from './alert/AlertHeading.vue';
export { default as AlertDescription } from './alert/AlertDescription.vue';
export { default as Empty } from './empty/Empty.vue';
export { default as EmptyContents } from './empty/EmptyContents.vue';
export { default as EmptyMedia } from './empty/EmptyMedia.vue';
export { default as DetailList } from './detail-list/DetailList.vue';
export { default as DetailListItem } from './detail-list/DetailListItem.vue';
export { default as StatCard } from './stat-card/StatCard.vue';
export { default as StatCardGroup } from './stat-card/StatCardGroup.vue';
export { default as Timeline } from './timeline/Timeline.vue';
export { default as TimelineItem } from './timeline/TimelineItem.vue';
export { default as Table } from './table/Table.vue';
export { default as TableHead } from './table/TableHead.vue';
export { default as TableBody } from './table/TableBody.vue';
export { default as TableRow } from './table/TableRow.vue';
export { default as TableHeader } from './table/TableHeader.vue';
export { default as TableCell } from './table/TableCell.vue';
export { default as TableFooter } from './table/TableFooter.vue';
export { default as PageHeader } from './page-header/PageHeader.vue';
export { default as Chart } from './chart/Chart.vue';

// Navigation
export { default as Tabs } from './tabs/Tabs.vue';
export { default as TabList } from './tabs/TabList.vue';
export { default as Tab } from './tabs/Tab.vue';
export { default as TabPanel } from './tabs/TabPanel.vue';
export { default as NavigationTab } from './navigation-tab/NavigationTab.vue';
export { default as NavigationTabGroup } from './navigation-tab/NavigationTabGroup.vue';
export { default as Accordion } from './accordion/Accordion.vue';
export { default as AccordionItem } from './accordion/AccordionItem.vue';
export { default as AccordionTrigger } from './accordion/AccordionTrigger.vue';
export { default as AccordionContent } from './accordion/AccordionContent.vue';
export { default as SidebarLink } from './sidebar/SidebarLink.vue';
export { default as SidebarDropdown } from './sidebar/SidebarDropdown.vue';
export { default as SidebarDropdownGroup } from './sidebar/SidebarDropdownGroup.vue';
export { default as SidebarDropdownLink } from './sidebar/SidebarDropdownLink.vue';
export { default as ThemeSwitcher } from './theme-switcher/ThemeSwitcher.vue';

// Overlays
export { default as Dropdown } from './dropdown/Dropdown.vue';
export { default as DropdownItem } from './dropdown/DropdownItem.vue';
export { default as DropdownGroup } from './dropdown/DropdownGroup.vue';
export { default as DropdownSeparator } from './dropdown/DropdownSeparator.vue';
export { default as DropdownSubmenu } from './dropdown/DropdownSubmenu.vue';
export { default as Tooltip } from './tooltip/Tooltip.vue';
export { default as Popup } from './popup/Popup.vue';
export { default as Modal } from './modal/Modal.vue';
export { default as ModalHeading } from './modal/ModalHeading.vue';
export { default as Confirm } from './confirm/Confirm.vue';
export { default as Toast } from './toast/Toast.vue';

export type { UiColor } from './colors';
export type { ChartSpec } from './chart/options';
export { useTheme } from '@/composables/useTheme';
export { useToast } from '@/composables/useToast';
