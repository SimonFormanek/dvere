<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

  require 'includes/application_top.php';

  $action = $_GET['action'] ?? '';

  $OSCOM_Hooks->call('advert_manager', 'preAction');

  if (!Text::is_empty($action)) {
    switch ($action) {
      case 'import':
      $languages = tep_get_languages();

      $import_query = tep_db_query("SELECT * FROM banners ORDER BY banners_id");
      while ($import = $import_query->fetch_assoc()) {
        $sql_data_array = ['advert_title'       => $import['banners_title'],
                           'advert_url'         => $import['banners_url'],
                           'advert_image'       => $import['banners_image'],
                           'advert_group'       => $import['banners_group'],
                           'date_added'         => $import['date_added'],
                           'date_status_change' => $import['date_status_change'],
                           'status'             => $import['status']];

        tep_db_perform('advert', $sql_data_array);

        $advert_id = tep_db_insert_id();

        foreach ($languages as $l) {
          $lng_data_array = ['advert_id'        => $advert_id,
                             'languages_id'     => $l['id'],
                             'advert_html_text' => $import['banners_html_text']];

          tep_db_perform('advert_info', $lng_data_array);
        }
      }

      $OSCOM_Hooks->call('advert_manager', 'importAction');

      $messageStack->add_session(SUCCESS_BANNERS_IMPORTED, 'success');

      tep_redirect(tep_href_link('advert_manager.php'));
      break;

      case 'setflag':
        if ( ($_GET['flag'] == '0') || ($_GET['flag'] == '1') ) {
          tep_db_query("UPDATE advert SET status = " . (int)$_GET['flag'] . ", date_status_change = NOW() WHERE advert_id = " . (int)$_GET['cID']);

          $messageStack->add_session(SUCCESS_ADVERT_STATUS_UPDATED, 'success');
        }

        $OSCOM_Hooks->call('advert_manager', 'setFlagAction');

        tep_redirect(tep_href_link('advert_manager.php', 'page=' . (int)$_GET['page'] . '&cID=' . (int)$_GET['cID']));
        break;
      case 'insert':
      case 'update':
        if (isset($_POST['advert_id'])) {
          $advert_id = Text::input($_POST['advert_id']);
        }
        $advert_title = Text::prepare($_POST['advert_title']);
        $advert_url = Text::prepare($_POST['advert_url']);
        $advert_fragment = Text::prepare($_POST['advert_fragment']);

        $new_advert_group = Text::prepare($_POST['new_advert_group']);
        $advert_group = (empty($new_advert_group)) ? Text::prepare($_POST['advert_group']) : $new_advert_group;
        $sort_order = Text::input($_POST['sort_order']);

        $advert_image_local = Text::prepare($_POST['advert_image_local']);
        $advert_image_target = Text::prepare($_POST['advert_image_target']);
        $db_image_location = '';

        $advert_error = false;
        if (empty($advert_title)) {
          $messageStack->add(ERROR_ADVERT_TITLE_REQUIRED, 'error');
          $advert_error = true;
        }

        if (empty($advert_group)) {
          $messageStack->add(ERROR_ADVERT_GROUP_REQUIRED, 'error');
          $advert_error = true;
        }

        $advert_image = new upload('advert_image');
        $advert_image->parse();

        if (empty($advert_image->filename)) {
          if ( empty($advert_image_local) && empty($_POST['advert_html_text']) ) {
            $messageStack->add(ERROR_ADVERT_IMAGE_OR_TEXT_REQUIRED, 'error');
            $advert_error = true;
          }
        } else {
          $advert_image->set_destination(DIR_FS_CATALOG . 'images/' . $advert_image_target);
          if ( $advert_image->save() == false ) {
            $advert_error = true;
          }
        }

        if ($advert_error == false) {
          $db_image_location = (Text::is_empty($advert_image_local)) ? $advert_image_target . $advert_image->filename : $advert_image_local;
          $sql_data_array = ['advert_title'     => $advert_title,
                             'advert_url'       => $advert_url,
                             'advert_fragment'  => $advert_fragment,
                             'advert_image'     => $db_image_location,
                             'advert_group'     => $advert_group,
                             'sort_order'       => $sort_order];

          if ($action == 'insert') {
            $insert_sql_data = ['date_added' => 'NOW()', 'status' => '1'];

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            $OSCOM_Hooks->call('advert_manager', 'insertAction');

            tep_db_perform('advert', $sql_data_array);

            $advert_id = tep_db_insert_id();

            $messageStack->add_session(SUCCESS_IMAGE_INSERTED, 'success');
          } elseif ($action == 'update') {
            $OSCOM_Hooks->call('advert_manager', 'updateAction');

            tep_db_perform('advert', $sql_data_array, 'update', "advert_id = " . (int)$advert_id);

            $messageStack->add_session(SUCCESS_IMAGE_UPDATED, 'success');
          }

          if ($action == 'insert' || $action == 'update') {
      shell_exec("img2webp -d 3300 -loop 0 -lossy -q 50 ../images/IMG_* -o ../images/rotator.webp");
          }

          foreach (tep_get_languages() as $l) {
            $lng_data_array['advert_html_text'] = Text::prepare($_POST['advert_html_text'][$l['id']]);

            if ($action == 'insert') {
              $insert_sql_data = ['advert_id' => $advert_id, 'languages_id' => $l['id']];

              $lng_data_array = array_merge($lng_data_array, $insert_sql_data);

              tep_db_perform('advert_info', $lng_data_array);
            } elseif ($action == 'update') {
              tep_db_perform('advert_info', $lng_data_array, 'update', "advert_id = " . (int)$advert_id . " AND languages_id = " . (int)$l['id']);
            }
          }

          $OSCOM_Hooks->call('advert_manager', 'insertUpdateAction');
          tep_redirect(tep_href_link('advert_manager.php', (isset($_GET['page']) ? 'page=' . (int)$_GET['page'] . '&' : '') . 'cID=' . $advert_id));
        } else {
          $action = 'new';
        }
        break;
      case 'deleteconfirm':
        $advert_id = Text::input($_GET['cID']);

        if (isset($_POST['delete_image']) && ($_POST['delete_image'] == 'on')) {
          $advert_query = tep_db_query("SELECT advert_image FROM advert WHERE advert_id = " . (int)$advert_id);
          $advert = $advert_query->fetch_assoc();

          if (is_file(DIR_FS_CATALOG . 'images/' . $advert['advert_image'])) {
            if (tep_is_writable(DIR_FS_CATALOG . 'images/' . $advert['advert_image'])) {
              unlink(DIR_FS_CATALOG . 'images/' . $advert['advert_image']);
            } else {
              $messageStack->add_session(ERROR_IMAGE_IS_NOT_WRITEABLE, 'error');
            }
          } else {
            $messageStack->add_session(ERROR_IMAGE_DOES_NOT_EXIST, 'error');
          }
        }

        tep_db_query("DELETE FROM advert WHERE advert_id = " . (int)$advert_id);
        tep_db_query("DELETE FROM advert_info WHERE advert_id = " . (int)$advert_id);

        $OSCOM_Hooks->call('advert_manager', 'deleteConfirmAction');

        $messageStack->add_session(SUCCESS_IMAGE_REMOVED, 'success');

        tep_redirect(tep_href_link('advert_manager.php', 'page=' . (int)$_GET['page']));
        break;
    }
  }

  $OSCOM_Hooks->call('advert_manager', 'postAction');

  require 'includes/template_top.php';
?>

  <div class="row">
    <div class="col">
      <h1 class="display-4 mb-2"><?= HEADING_TITLE; ?></h1>
    </div>
    <div class="col text-right align-self-center">
      <?php
      if (isset($_GET['action']) && ($_GET['action'] == 'new')) {
        echo tep_draw_bootstrap_button(IMAGE_BACK, 'fas fa-angle-left', tep_href_link('advert_manager.php'), null, null, 'btn-light');
      } else {
        echo tep_draw_bootstrap_button(IMAGE_NEW_ADVERT, 'fas fa-map-marker-alt', tep_href_link('advert_manager.php', 'action=new'), null, null, 'btn-danger');
      }
      ?>
    </div>
  </div>

<?php
  if ($action == 'new') {
    $languages = tep_get_languages();

    $form_action = 'insert';

    $parameters = ['advert_title' => '', 'advert_url' => '', 'advert_fragment' => '', 'advert_group' => '', 'advert_image' => '', 'sort_order' => '', 'advert_html_text' => ''];

    $cInfo = new objectInfo($parameters);

    if (isset($_GET['cID'])) {
      $form_action = 'update';

      $cID = Text::input($_GET['cID']);

      $advert_query = tep_db_query("SELECT * FROM advert WHERE advert_id = " . (int)$cID);
      $advert = $advert_query->fetch_assoc();

      $cInfo->objectInfo($advert);
    } elseif (!empty($_POST)) {
      $cInfo->objectInfo($_POST);
    }

    $groups_array = [];
    $groups_query = tep_db_query("SELECT DISTINCT advert_group FROM advert ORDER BY advert_group");
    while ($groups = $groups_query->fetch_assoc()) {
      $groups_array[] = ['id' => $groups['advert_group'], 'text' => $groups['advert_group']];
    }

    echo tep_draw_form('new_advert', 'advert_manager.php', (isset($_GET['page']) ? 'page=' . (int)$_GET['page'] . '&' : '') . 'action=' . $form_action, 'post', 'enctype="multipart/form-data"');
    if ('update' === $form_action) {
      echo tep_draw_hidden_field('advert_id', $cID);
    }
    ?>

      <div class="form-group row" id="zTitle">
        <label for="aTitle" class="col-form-label col-sm-3 text-left text-sm-right"><?= TEXT_ADVERT_TITLE; ?></label>
        <div class="col-sm-9">
          <?= tep_draw_input_field('advert_title', $cInfo->advert_title, 'class="form-control" id="aTitle" required aria-required="true" aria-describedby="aTitleHelp"'); ?>
          <small id="aTitleHelp" class="form-text text-muted"><?= TEXT_ADVERT_TITLE_HELP; ?></small>
        </div>
      </div>

      <div class="form-group row" id="zUrlFrag">
        <label for="aUrl" class="col-form-label col-sm-3 text-left text-sm-right"><?= TEXT_ADVERT_URL; ?></label>
        <div class="col-sm-9">
          <div class="row">
            <div class="col">
              <?= tep_draw_input_field('advert_url', $cInfo->advert_url, 'class="form-control" id="aUrl" aria-describedby="aUrlHelp"'); ?>
              <small id="aUrlHelp" class="form-text text-muted"><?= TEXT_ADVERT_URL_HELP; ?></small>
            </div>
            <div class="col">
              <?= tep_draw_input_field('advert_fragment', $cInfo->advert_fragment, 'placeholder="' . TEXT_ADVERT_FRAGMENT . '" class="form-control" id="cFrag" aria-describedby="cFragHelp"'); ?>
              <small id="cFragHelp" class="form-text text-muted"><?= TEXT_ADVERT_FRAGMENT_HELP; ?></small>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group row" id="zSort">
        <label for="aSort" class="col-form-label col-sm-3 text-left text-sm-right"><?= TEXT_ADVERT_SORT_ORDER; ?></label>
        <div class="col-sm-9">
          <?= tep_draw_input_field('sort_order', $cInfo->sort_order, 'class="form-control w-25" id="aSort" aria-describedby="aSortHelp"'); ?>
          <small id="aSortHelp" class="form-text text-muted"><?= TEXT_ADVERT_SORT_HELP; ?></small>
        </div>
      </div>

      <hr>

      <div class="form-group row" id="zGroup">
        <label for="aGroup" class="col-form-label col-sm-3 text-left text-sm-right"><?= TEXT_ADVERT_GROUP; ?></label>
        <div class="col-sm-9">
          <div class="row">
            <div class="col">
              <?= tep_draw_pull_down_menu('advert_group', $groups_array, $cInfo->advert_group, 'id="aGroup" class="form-control"'); ?>
            </div>
            <div class="col">
              <?= tep_draw_input_field('new_advert_group', '', 'placeholder="' . TEXT_ADVERT_NEW_GROUP . '" class="form-control" id="aNewGroup"'); ?>
            </div>
          </div>
        </div>
      </div>

      <hr>

      <div class="form-group row" id="zImage">
        <label class="col-form-label col-sm-3 text-left text-sm-right"><?= TEXT_ADVERT_IMAGE; ?></label>
        <div class="col-sm-9">
          <div class="row">
            <div class="col">
              <div class="custom-file mb-2">
                <?= tep_draw_input_field('advert_image', '', 'id="advert_image"', 'file', null, 'class="custom-file-input"'); ?>
                <label class="custom-file-label" for="advert_image"></label>
              </div>
            </div>
            <div class="col">
              <?= tep_draw_input_field('advert_image_local', (isset($cInfo->advert_image) ? $cInfo->advert_image : ''), 'placeholder="' . TEXT_ADVERT_IMAGE_LOCAL . '" class="form-control" id="cNewImage"'); ?>
            </div>
            <div class="col">
              <?= tep_draw_input_field('advert_image_target', null, 'placeholder="' . TEXT_ADVERT_IMAGE_TARGET . '" class="form-control" id="cTarget"'); ?>
            </div>
          </div>
        </div>
      </div>

      <hr>

      <?php
      foreach ($languages as $l) {
        ?>
        <div class="form-group row" id="zText<?= $l['directory']; ?>">
          <label for="aText<?= $l['id']; ?>" class="col-form-label col-sm-3 text-left text-sm-right"><?= TEXT_ADVERT_HTML_TEXT; ?></label>
          <div class="col-sm-9">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><?= tep_image(tep_catalog_href_link('includes/languages/' . $l['directory'] . '/images/' . $l['image']), $l['name']); ?></span>
              </div>
              <?= tep_draw_textarea_field('advert_html_text[' . $l['id'] . ']', 'soft', '60', '5', empty($cInfo->advert_id) ? '' : adverts::advert_get_html_text($cInfo->advert_id, $l['id']), 'class="form-control" id="aText' . $l['id'] . '"') ?>
            </div>
          </div>
        </div>
        <?php
      }
      ?>

      <div class="alert alert-info">
        <?= TEXT_ADVERT_NOTE . TEXT_INSERT_NOTE; ?>
      </div>

      <?php
      if ($form_action == 'update') {
        echo $OSCOM_Hooks->call('advert_manager', 'editForm');
      } else {
        echo $OSCOM_Hooks->call('advert_manager', 'newForm');
      }
      ?>

      <div class="buttonSet">
        <?= tep_draw_bootstrap_button(IMAGE_SAVE, 'fas fa-images', null, null, null, 'btn-success btn-block btn-lg'); ?>
      </div>

    </form>

    <script>$(document).on('change', '#advert_image', function (event) { $(this).next('.custom-file-label').html(event.target.files[0].name); });</script>
<?php
  } else {
?>

  <div class="row no-gutters">
    <div class="col-12 col-sm-8">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="thead-dark">
            <tr>
              <th><?= TABLE_HEADING_ADVERT; ?></th>
              <th class="text-right"><?= TABLE_HEADING_GROUP; ?></th>
              <th class="text-right"><?= TABLE_HEADING_SORT_ORDER; ?></th>
              <th class="text-right"><?= TABLE_HEADING_STATUS; ?></th>
              <th class="text-right"><?= TABLE_HEADING_ACTION; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
            $advert_query_raw = "SELECT * FROM advert a, advert_info ai WHERE a.advert_id = ai.advert_id AND ai.languages_id = '" . $_SESSION['languages_id'] . "' ORDER BY a.advert_group, a.sort_order, a.advert_title";
            $advert_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $advert_query_raw, $advert_query_numrows);
            $advert_query = tep_db_query($advert_query_raw);
            while ($advert = $advert_query->fetch_assoc()) {
              if (!isset($cInfo) && (!isset($_GET['cID']) || ($_GET['cID'] == $advert['advert_id'])) && (substr($action, 0, 3) != 'new')) {
                $cInfo = new objectInfo($advert);
              }

              if (isset($cInfo->advert_id) && ($advert['advert_id'] == $cInfo->advert_id)) {
                echo '<tr class="table-active">';
                $icon = '<i class="fas fa-chevron-circle-right text-info"></i>';
              } else {
                echo '<tr onclick="document.location.href=\'' . tep_href_link('advert_manager.php', 'page=' . (int)$_GET['page'] . '&cID=' . (int)$advert['advert_id']) . '\'">';
                $icon = '<a href="' . tep_href_link('advert_manager.php', 'page=' . (int)$_GET['page'] . '&cID=' . $advert['advert_id']) . '"><i class="fas fa-info-circle text-muted"></i></a>';
              }
              ?>
                <td><?= $advert['advert_title']; ?></td>
                <td class="text-right"><?= $advert['advert_group']; ?></td>
                <td class="text-right"><?= $advert['sort_order'] ?? 0; ?></td>
                <td class="text-right"><?php if ($advert['status'] == '1') { echo '<i class="fas fa-check-circle text-success"></i> <a href="' . tep_href_link('advert_manager.php', 'page=' . (int)$_GET['page'] . '&cID=' . (int)$advert['advert_id'] . '&action=setflag&flag=0') . '"><i class="fas fa-times-circle text-muted"></i></a>'; } else { echo '<a href="' . tep_href_link('advert_manager.php', 'page=' . (int)$_GET['page'] . '&cID=' . $advert['advert_id'] . '&action=setflag&flag=1') . '"><i class="fas fa-check-circle text-muted"></i></a> <i class="fas fa-times-circle text-danger"></i>'; } ?></td>
                <td class="text-right"><?= $icon ?></td>
              </tr>
              <?php
            }
            ?>
          </tbody>
        </table>
      </div>

      <div class="row my-1">
        <div class="col"><?= $advert_split->display_count($advert_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_ADVERTS); ?></div>
        <div class="col text-right mr-2"><?= $advert_split->display_links($advert_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></div>
      </div>

      <?php
      $banner_table_query = tep_db_query("SHOW TABLES LIKE 'banners'");
      if ($advert_query_numrows == 0 && mysqli_num_rows($banner_table_query)) {
        echo '<div class="alert alert-info mt-3">';
          echo '<div class="row align-items-center">';
            echo '<div class="col-4">';
              echo tep_draw_bootstrap_button(IMAGE_IMPORT_ADVERT, 'fas fa-database', tep_href_link('advert_manager.php', 'action=import'), null, null, 'btn-info btn-block');
            echo '</div>';
            echo '<div class="col">';
              echo IMAGE_IMPORT_ADVERT_EXPLANATION;
            echo '</div>';
          echo '</div>';
        echo '</div>';
      }
      ?>

    </div>

<?php
  $heading = [];
  $contents = [];
  switch ($action) {
    case 'delete':
      $heading[] = ['text' => $cInfo->advert_title];

      $contents = ['form' => tep_draw_form('advert', 'advert_manager.php', 'page=' . (int)$_GET['page'] . '&cID=' . (int)$cInfo->advert_id . '&action=deleteconfirm')];
      $contents[] = ['text' => TEXT_INFO_DELETE_INTRO];
      $contents[] = ['class' => 'text-center text-uppercase font-weight-bold', 'text' => $cInfo->advert_title];
      if ($cInfo->advert_image) {
        $contents[] = ['text' => '<div class="custom-control custom-switch">' . tep_draw_selection_field('delete_image', 'checkbox', 'on', null, 'class="custom-control-input" id="aDelImg"') . '<label for="aDelImg" class="custom-control-label text-muted"><small>' . TEXT_INFO_DELETE_IMAGE . '</small></label></div>'];
      }
      $contents[] = ['align' => 'center', 'text' => tep_draw_bootstrap_button(IMAGE_DELETE, 'fas fa-trash', null, 'primary', null, 'btn-danger mr-2') . tep_draw_bootstrap_button(IMAGE_CANCEL, 'fas fa-angle-left', tep_href_link('advert_manager.php', 'page=' . (int)$_GET['page'] . '&cID=' . (int)$_GET['cID']), null, null, 'btn-light')];
      break;
    default:
     if (isset($cInfo) && is_object($cInfo)) {
        $heading[] = ['text' => $cInfo->advert_title];

        $contents[] = ['align' => 'center', 'text' => tep_draw_bootstrap_button(IMAGE_EDIT, 'fas fa-cogs', tep_href_link('advert_manager.php', 'page=' . (int)$_GET['page'] . '&cID=' . $cInfo->advert_id . '&action=new'), null, null, 'btn-warning mr-2') . tep_draw_bootstrap_button(IMAGE_DELETE, 'fas fa-trash', tep_href_link('advert_manager.php', 'page=' . (int)$_GET['page'] . '&cID=' . $cInfo->advert_id . '&action=delete'), null, null, 'btn-danger')];
        $contents[] = ['text' => sprintf(TEXT_ADVERT_DATE_ADDED, tep_date_short($cInfo->date_added))];

        if (!Text::is_empty($cInfo->advert_url)) {
          if (filter_var($cInfo->advert_url, FILTER_VALIDATE_URL)) {
            $contents[] = ['text' => sprintf(TEXT_ADVERT_EXTERNAL_URL, $cInfo->advert_url)];
          } else {
            $fragment = $cInfo->advert_fragment ?? null;
            $contents[] = ['text' => sprintf(TEXT_ADVERT_INTERNAL_URL, tep_catalog_href_link($cInfo->advert_url, $fragment))];
          }
        }

        if (!Text::is_empty($cInfo->advert_image)) {
          $contents[] = ['text' => tep_info_image($cInfo->advert_image, $cInfo->advert_image)];
        }
        if (!Text::is_empty($cInfo->advert_html_text)) {
          $contents[] = ['text' => $cInfo->advert_html_text];
        }

        if ($cInfo->date_status_change) {
          $contents[] = ['text' => sprintf(TEXT_ADVERT_STATUS_CHANGE, tep_date_short($cInfo->date_status_change))];
        }
      }
      break;
  }

  if ( ([] !== $heading) && ([] !== $contents) ) {
     echo '<div class="col-12 col-sm-4">';
      $box = new box();
      echo $box->infoBox($heading, $contents);
    echo '</div>';
  }

  echo '</div>';
}

  require 'includes/template_bottom.php';
  require 'includes/application_bottom.php';
?>
