<?php
	$addresses_count = tep_count_customer_address_book_entries();

	ob_start();
	?>
<table cellpadding="0" cellspacing="0" border="0" width="400">
<?php 
  if ($addresses_count < MAX_ADDRESS_BOOK_ENTRIES) {
?>
 <tr>
  <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
   <tr>
    <td class="main"><b><?php echo sysLanguage::get('TABLE_HEADING_NEW_ADDRESS') . tep_draw_hidden_field('action', 'addNewAddress'); ?></b></td>
   </tr>
  </table></td>
 </tr>
 <tr>
  <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
   <tr class="infoBoxContents">
    <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
     <tr>
      <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
      <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
       <tr>
        <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
<?php
  if (ACCOUNT_GENDER == 'true') {
    if (isset($gender)) {
      $male = ($gender == 'm') ? true : false;
      $female = ($gender == 'f') ? true : false;
    } else {
      $male = false;
      $female = false;
    }
?>
         <tr>
          <td class="main"><?php echo sysLanguage::get('ENTRY_GENDER'); ?></td>
          <td class="main"><?php echo tep_draw_radio_field('gender', 'm', $male) . '&nbsp;&nbsp;' . MALE . '&nbsp;&nbsp;' . tep_draw_radio_field('gender', 'f', $female) . '&nbsp;&nbsp;' . FEMALE . '&nbsp;' . (tep_not_null(sysLanguage::get('ENTRY_GENDER_TEXT')) ? '<span class="inputRequirement">' . sysLanguage::get('ENTRY_GENDER_TEXT') . '</span>': ''); ?></td>
         </tr>
<?php
  }
?>
         <tr>
          <td class="main"><?php echo sysLanguage::get('ENTRY_FIRST_NAME'); ?></td>
          <td class="main"><?php echo tep_draw_input_field('firstname') . '&nbsp;' . (tep_not_null(sysLanguage::get('ENTRY_FIRST_NAME_TEXT')) ? '<span class="inputRequirement">' . sysLanguage::get('ENTRY_FIRST_NAME_TEXT') . '</span>': ''); ?></td>
         </tr>
         <tr>
          <td class="main"><?php echo sysLanguage::get('ENTRY_LAST_NAME'); ?></td>
          <td class="main"><?php echo tep_draw_input_field('lastname') . '&nbsp;' . (tep_not_null(sysLanguage::get('ENTRY_LAST_NAME_TEXT')) ? '<span class="inputRequirement">' . sysLanguage::get('ENTRY_LAST_NAME_TEXT') . '</span>': ''); ?></td>
         </tr>
<?php
  if (ACCOUNT_COMPANY == 'true') {
?>
         <tr>
          <td class="main"><?php echo sysLanguage::get('ENTRY_COMPANY'); ?></td>
          <td class="main"><?php echo tep_draw_input_field('company') . '&nbsp;' . (tep_not_null(sysLanguage::get('ENTRY_COMPANY_TEXT')) ? '<span class="inputRequirement">' . sysLanguage::get('ENTRY_COMPANY_TEXT') . '</span>': ''); ?></td>
         </tr>
<?php
  }
?>
         <tr>
          <td class="main"><?php echo sysLanguage::get('ENTRY_STREET_ADDRESS'); ?></td>
          <td class="main"><?php echo tep_draw_input_field('street_address') . '&nbsp;' . (tep_not_null(sysLanguage::get('ENTRY_STREET_ADDRESS_TEXT')) ? '<span class="inputRequirement">' . sysLanguage::get('ENTRY_STREET_ADDRESS_TEXT') . '</span>': ''); ?></td>
         </tr>
<?php
  if (ACCOUNT_SUBURB == 'true') {
?>
         <tr>
          <td class="main"><?php echo sysLanguage::get('ENTRY_SUBURB'); ?></td>
          <td class="main"><?php echo tep_draw_input_field('suburb') . '&nbsp;' . (tep_not_null(sysLanguage::get('ENTRY_SUBURB_TEXT')) ? '<span class="inputRequirement">' . sysLanguage::get('ENTRY_SUBURB_TEXT') . '</span>': ''); ?></td>
         </tr>
<?php
  }
?>
         <tr>
          <td class="main"><?php echo sysLanguage::get('ENTRY_CITY'); ?></td>
          <td class="main"><?php echo tep_draw_input_field('city') . '&nbsp;' . (tep_not_null(sysLanguage::get('ENTRY_CITY_TEXT')) ? '<span class="inputRequirement">' . sysLanguage::get('ENTRY_CITY_TEXT') . '</span>': ''); ?></td>
         </tr>
<?php
  if (ACCOUNT_STATE == 'true') {
?>
         <tr>
          <td class="main"><?php echo sysLanguage::get('ENTRY_STATE'); ?></td>
          <td class="main" id="stateCol"><?php echo tep_draw_input_field('state') . '&nbsp;' . (tep_not_null(sysLanguage::get('ENTRY_STATE_TEXT')) ? '<span class="inputRequirement">' . sysLanguage::get('ENTRY_STATE_TEXT') . '</span>' : ''); ?></td>
         </tr>
<?php
  }
?>
         <tr>
          <td class="main"><?php echo sysLanguage::get('ENTRY_POST_CODE'); ?></td>
          <td class="main"><?php echo tep_draw_input_field('postcode') . '&nbsp;' . (tep_not_null(sysLanguage::get('ENTRY_POST_CODE_TEXT')) ? '<span class="inputRequirement">' . sysLanguage::get('ENTRY_POST_CODE_TEXT') . '</span>': ''); ?></td>
         </tr>
         <tr>
          <td class="main"><?php echo sysLanguage::get('ENTRY_COUNTRY'); ?></td>
          <td class="main"><?php echo tep_get_country_list('country') . '&nbsp;' . (tep_not_null(sysLanguage::get('ENTRY_COUNTRY_TEXT')) ? '<span class="inputRequirement">' . sysLanguage::get('ENTRY_COUNTRY_TEXT') . '</span>': ''); ?></td>
         </tr>
        </table></td>
        <td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
       </tr>
      </table></td>
      <td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
     </tr>
    </table></td>
   </tr>
  </table></td>
 </tr>
<?php
  }    
?>   
</table>
	<?php
	$html = ob_get_contents();
	ob_end_clean();
	
	EventManager::attachActionResponse($html, 'html');
?>