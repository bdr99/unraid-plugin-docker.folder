<style>
  .containers {
    display: grid;
    grid-template-columns: auto auto auto;
  }

  .container_item {
    border: 1px solid rgba(0, 0, 0, 0.8);
  }

  .settingC-box {
    float: right;
  }

  .info {
    display: inline;
  }

  .docker_img {
    float: left;
    width: 48px;
    height: 48px;
    padding-right: 5px;
  }

  .disabled {
    background-color: rgb(0, 0, 0, 0.3);
    filter: grayscale(70%);
  }

  .checked {
    background-color: rgb(0, 200, 30, 0.3);
  }

  #icon-upload {
    display: none;
  }

  #icon-upload-label {
    cursor: pointer;
    left: -54px;
    position: relative;
  }

  #icon-upload-preview {
    height: 44px;
    width: 44px;
    left: -44px;
    position: relative;
  }

  #icon-upload-input {
    left: -48px;
    position: relative;
  }

  .fa-icon {
    left: -20px;
    position: relative;
  }

  .fa-input {
    position: relative;
  }

  .type-info {
    position: relative;
    top: -7px;
    left: 22px;
    font-size: small;
  }
</style>



<?php
require_once("/usr/local/emhttp/plugins/docker.folder/include/common.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");

require_once("/usr/local/emhttp/plugins/docker.folder/include/popup.php");

function searchArray($array, $key, $value)
{
  if (function_exists("array_column") && function_exists("array_search")) {   # faster to use built in if it works
    $result = array_search($value, array_column($array, $key));
  } else {
    $result = false;
    for ($i = 0; $i <= max(array_keys($array)); $i++) {
      if ($array[$i][$key] == $value) {
        $result = $i;
        break;
      }
    }
  }
  return $result;
}


$DockerTemplates = new DockerTemplates();
$info = $DockerTemplates->getAllInfo();
$DockerClient = new DockerClient();
$moreInfo = $DockerClient->getDockerContainers();

$dockerSettings = "<div class='containers'>";
$containerNames = array_keys($info);

foreach ($containerNames as $container) {

  if (endsWith($container, "-folder")) {
    continue;
  }

  $img = $info[$container]['icon'];
  if ($img == null) {
    $img = "/plugins/dynamix.docker.manager/images/question.png";
  }

  $index = searchArray($moreInfo, "Name", $container);
  $repository = ($index === false) ? "Unknown" : $moreInfo[$index]['Image'];
  $id = ($index === false) ? "Unknown" : $moreInfo[$index]['Id'];
  $dockerSettings .= "<div class='container_item'>";
  $dockerSettings .= "<div class='info'><img class='docker_img' src='" . $img . "'>";

  $dockerSettings .= "<strong>$container</strong><br>$repository";

  $dockerSettings .= "<div class='container-id' style='display:none;'>$id</div></div>";

  $dockerSettings .= "<div class='settingC-box'><input class='settingC' type='checkbox' name='$container'></div>";

  $dockerSettings .= "</div>";
}
$dockerSettings .= "</div>";

function endsWith($haystack, $needle)
{
  return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}





?>
<div id="docker_tabbed" style="float:right;margin-top:-55px"></div>
<div>
  <form id="form" onsubmit="return false">
    <dl>
      <dt>Name:</dt>
      <dd><input class="setting" type="text" name="name" pattern="[^\s]+" title="no spaces please :)" required></dd>

      <dt>Icon:</dt>
      <dd>
        <img id="icon-upload-preview" src="/plugins/dynamix.docker.manager/images/question.png">
        <input id="icon-upload-input" class="setting" type="text" name="icon">
        <label id="icon-upload-label" for="icon-upload" class="fa fa-upload fa-lg" aria-hidden="true">
          <input id="icon-upload" type="file" onchange="iconEncodeImageFileAsURL(this)" />
      </dd>

      <div class="advanced" style="display: none">
        <dt>Start expanded</dt>
        <dd><input class="basic-switch setting" name="start_expanded" type="checkbox" /></dd>
      </div>

      <div id="dialogAddConfig" style="display:none"></div>
      <div id="buttonLocation"></div>

      <table class="settings">
        <tr>
          <td></td>
          <td><a href="javascript:addConfigPopup()"><i class="fa fa-plus"></i> Add another Button</a></td>
        </tr>
      </table>
      <table class="settings">
        <tr>
          <td></td>
          <td><a href="javascript:addDivider()"><i class="fa fa-plus"></i> Add another Divider</a></td>
        </tr>
      </table>

      <div id="dockers">
        <?= $dockerSettings ?>
      </div>
      <br>

      <table class="settings">
        <tr>
          <td></td>
          <td>
            <input type="submit" value="Submit">
          </td>
        </tr>
      </table>
      <br><br><br>
    </dl>
  </form>
</div>

<link type="text/css" rel="stylesheet" href="/webGui/styles/jquery.switchbutton.css">
<script src="/plugins/docker.folder/include/jquery.switchbutton-latest.js"></script>
<script src="/plugins/dynamix.vm.manager/javascript/dynamix.vm.manager.js"></script>

<script>
  let url = new URLSearchParams(window.location.search)
  editFolderName = url.get("folderName")

  init()

  async function init() {
    folders = await read_folders()
    let folderNames = Object.keys(await folders)

    $('.settingC').each(function() {
      for (const folderName of folderNames) {
        let folderChild = folders[folderName]['children']
        for (const child of folderChild) {
          if ($(this).attr('name') == child && folderName !== editFolderName) {
            $(this).prop("disabled", true)
            $(this).parent().parent().addClass("disabled")
          } else if (folderName == editFolderName && $(this).attr('name') == child) {
            $(this).prop("checked", true)
            $(this).parent().parent().addClass("checked")
          }
        }
      }
      // add switch button
      $(this).switchButton({
        show_labels: false
      });

    });


    for (const folderName of folderNames) {
      if (folderName == editFolderName) {
        $('.setting').each(function() {
          switch ($(this).attr('name')) {
            case "name":
              $(this).val(folderName)
              $(this).attr("disabled", true)
              break;

            case "icon":
              $(this).val(folders[folderName]['icon'])
              break;

            case "start_expanded":
              $(this).prop('checked', folders[folderName]['start_expanded'])
            break;
          }
        })

        loadButtons(folders, folderName)

      }
    }

    //make it green
    $('.container_item > .settingC-box > input[type="checkbox"]').change(function() {
      if ($(this).prop("checked") == true) {
        $(this).parent().parent().addClass("checked")
      } else {
        $(this).parent().parent().removeClass("checked")
      }
    })


    // buttons sortable

    var sortableHelper = function(e, i) {
      i.children().each(function() {
        $(this).width($(this).width());
      });
      return i;
    };

    $('#buttonLocation').sortable({
      helper: sortableHelper,
      items: 'div.sortable',
      cursor: 'move',
      axis: 'y',
      containment: 'parent',
      cancel: 'span.docker_readmore,input',
      delay: 100,
      opacity: 0.5,
      zIndex: 9999
    })

    $('.basic-switch').switchButton({
      show_labels: false
    });

  }


  function getSettings() {
    let settings = new Object()

    $(".setting").each(function() {
      var value = $(this).val();
      var name = $(this).attr('name');
      if ((typeof value != "string")) {
        var value = "something really went wrong here";
      }
      if ((value == null)) {
        value = " ";
      }
      value = value.trim();

      // get true/false for checkbox input
      if (name == 'start_expanded') {
        value = $(this).prop('checked')
      }

      settings[name] = value;
    });

    settings["children"] = folder_children;

    var folder_children = new Array();
    $(".settingC").each(function() {
      var value = $(this).prop("checked");
      var name = $(this).attr('name');
      if (value == true) {
        folder_children.push(name)
      }


    });
    settings["buttons"] = buttonAdd()
    settings["children"] = folder_children;
    return settings;
  }



  function buttonAdd() {
    // want to add popup like for add Label/Port/Path

    var tmp_array = new Array();
    $("#buttonLocation > [id*='ConfigNum-']").each(function() {
      let button = new Object()

      $(this).find("input").each(function() {
        var name = $(this).attr("name").replace("conf", "").replace("[]", "").toLowerCase();
        if ($(this).attr('type') == 'hidden' && $(this).val() !== "") {
          button[name] = $(this).val()
        }
      });

      tmp_array.push(button)
    });

    return tmp_array
  }

  // add event listen for form submit
  $('#form').submit(function() {
    submit()
  })

  async function submit() {
    $('input[type=button]').prop('disabled', true);

    let settings = await getSettings()

    if (editFolderName == null) {
      let dockerId = await createDocker(settings["name"])
      settings["id"] = dockerId
    } else {
      settings["id"] = folders[editFolderName]['id']
    }

    console.log(settings)

    let settingsSting = JSON.stringify(settings)
    $.post("/plugins/docker.folder/scripts/save_folder.php", {
      settings: settingsSting
    });

    //lazy fck
    location.replace(`/${location.href.split("/")[3]}`)
  }

  async function createDocker(name) {
    return postResult = await Promise.resolve($.get("/plugins/docker.folder/scripts/docker_folder_create.php", {
      name: name
    }));
  }

  // event listen for icon input change. Sets preview
  $("#icon-upload-input").on("input", function() {
    $("#icon-upload-preview").attr('src', $(this).val())
  });

  function iconEncodeImageFileAsURL(element) {
    var file = element.files[0];
    // 3mb
    if (file.size < 3145728) {
      var reader = new FileReader();
      reader.onloadend = function() {
        $("#icon-upload-input").val(reader.result)
        $("#icon-upload-preview").attr('src', reader.result)
      }
      reader.readAsDataURL(file);
    } else {
      swal({
        title: "Too large",
        text: "images above the 3mb-5mb range cause issues ;(",
        type: "warning"
      })
    }

  }

  var this_tab = $('input[name$="tabs"]').length;
  $(function() {
    var content = "<div class='switch-wrapper'><input type='checkbox' class='advanced-switch'></div>";
    <? if (!$tabbed) : ?>
      $("#docker_tabbed").html(content);
    <? else : ?>
      var last = $('input[name$="tabs"]').length;
      var elementId = "normalAdvanced";
      $('.tabs').append("<span id='" + elementId + "' class='status vhshift' style='display:none;'>" + content + "&nbsp;</span>");
      if ($('#tab' + this_tab).is(':checked')) {
        $('#' + elementId).show();
      }
      $('#tab' + this_tab).bind({
        click: function() {
          $('#' + elementId).show();
        }
      });
      for (var x = 1; x <= last; x++)
        if (x != this_tab) $('#tab' + x).bind({
          click: function() {
            $('#' + elementId).hide();
          }
        });
    <? endif; ?>
    $('.advanced-switch').switchButton({
      labels_placement: "left",
      on_label: 'Advanced View',
      off_label: 'Basic View'
    });
    $('.advanced-switch').change(function() {
      var status = $(this).is(':checked');
      toggleRows('advanced', status, 'basic');
    });
  });
</script>