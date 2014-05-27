{*
 * This file is part of the DoNotContactUntil extension.
 * It moves DoNotContactUntil's preferences next to the relevant
 * standard communication preferences on the 'Edit Contact' form.
 *}
{literal}
<script>
  function dncuMoveCheckbox(index) {
    // Select from the preceding <br> to the following <br> around the checkbox,
    // and move the checkbox and label to the corresponding cell created next to the date picker,
    // so the checkboxes and labels line up with their date fields.
    var doNotType = cj(this).attr('id').replace('privacy_do_not_', '');
    var toMove = cj(this).prevUntil('br').nextUntil('br').andSelf();
    var target = cj('#dncuPrefs input[data-crm-custom*="not_' + doNotType + '_until"]').parent('td').siblings('.dncuExistPref');
    toMove.appendTo(target);
  }

  cj(document).ready(function() {
    // Assign IDs to the table cell containing the existing privacy options...
    cj('#commPrefs input[id^="privacy_do_not_"]').parent('td').attr('id', 'commPrefsPriv');
    // and the table containing the extension's date fields
    cj('#Do_Not_Contact_Until table.form-layout-compressed').attr('id', 'dncuPrefs');

    // Remove the date fields' own labels, replacing with 'until'
    cj('#dncuPrefs td.label').next().prepend('until&nbsp;&nbsp;');
    cj('#dncuPrefs td.label').remove();
    // Create another table cell before each date field, to hold the checkboxes
    cj('<td/>', { class: 'dncuExistPref' }).prependTo(cj('#dncuPrefs tr'));
    // For each of the checkboxes with their labels, move them next to their date field
    cj('#commPrefs input[id^="privacy_do_not_"]').each(dncuMoveCheckbox);

    // Move the now-assembled table back where the privacy options came from
    cj('#dncuPrefs').appendTo('#commPrefsPriv');
    // Hide our now-unused custom fields dropdown
    cj('#Do_Not_Contact_Until').hide();
    // Remove all the <br>s that once separated the checkboxes...
    cj('#commPrefsPriv').children('br').remove();
    // but add one back to keep 'Privacy' in line with 'Preferred Method(s)'
    cj('#commPrefsPriv').prepend('<br/>');
  });
</script>
{/literal}
