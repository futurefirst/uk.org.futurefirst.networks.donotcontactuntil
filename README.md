Do Not Contact Until
====================

David Knoll, Future First
david@futurefirst.org.uk

An extension for CiviCRM. Allows a contact's Do Not Contact preferences
to be marked with dates when that preference will expire.

Usage:
  Once installed, date fields will appear next to the "Do not phone",
"Do not email" etc. boxes under the "Communication Preferences" heading
when you are editing a contact (in civicrm/contact/add). Just select
the date when you tick the box, and when it gets to that date the box
will automatically be unticked.

Implementation details:
  Creates a custom field group and a scheduled job. Uninstalling the
extension will remove these. An extra.tpl file is used to position the
date fields on the edit page.
