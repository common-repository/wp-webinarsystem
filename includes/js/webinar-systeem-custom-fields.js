jQuery(function ($) {
  function optionsFromString (input) {
    if (!input || !input.length) {
      return [];
    }

    return input
      .replace(/\r\n/g, '\r')
      .replace(/\n/g, '\r')
      .split(/\r/)
      .map((option) => {
        var parts = option.split('|');
        return {
          label: parts[0],
          value: parts.length > 1
            ? parts[1] : null
        }
      });
  }

  function optionsToString (options) {
    if (!options) {
      return '';
    }

    return options
      .map(option => {
        if (option.value) {
          return `${option.label}|${option.value}`;
        }
        return option.label;
      })
      .join('\n');
  }

  function addField(type, id, value, isOld, isRequired, options) {
    isOld = typeof isOld == 'undefined'
      ? false
      : isOld;

    value = value || '';
    id = id || makeId();

    var pHolder = isOld ? value : `Preview ${type} box`;
    var previewInput = `
      <input
        class="ws-custom-input-field-preview-input"
        type="${type}"
        data-id="${id}" placeholder="${pHolder}" disabled checked
      >`;

    var label = `
      <input
        class="ws-custom-input-field-label"
        data-id="${id}"
        value="${value}"
        placeholder="Field label"
      >`;

    var required = `
      <label>
        <input
          type="checkbox"
          class="ws-custom-input-field-required"
          ${(isRequired ? 'checked' : '')}
          id="ws-custom-input-required-${id}"
          class="ws-custom-input-field-required"
          data-id="${id}">
        Required
      </label>`;

    let field = label;

    // handle checkbox
    if (type === 'checkbox') {
      field = `
        <label class="radio">
          ${previewInput}
        </label>
        ${label}
      `;
    }
    
    // handle select
    if (type === 'select') {
      var optionsValue = optionsToString(options);

      field = `
        <div class="custom-select-container">
          <div>
            <label>Dropdown Options</label>
            <br/>
            <textarea
              class="ws-custom-input-field-select-options"
              data-id="${id}"
              placeholder="Option 1|value1\nOption 2|value\nOption 3|value3\n..." rows="8">${optionsValue}</textarea>
          </div>
        </div>
        ${label}
      `;
    }

    var includeRequired = type !== 'checkbox';

    var fieldSet = `
      <li class="ui-state-default" data-id="${id}" data-type="${type}">
        ${field}
        ${(includeRequired ? required : '')}
        <i class="fa fa-reorder"></i>
        <i class="fa fa-close remove-custom-regfield" data-id="${id}" ></i></div>
        <div style="clear: both;"></div>
      </li>`;

    $('.ws-custom-field-container').append(fieldSet);

    if (!isOld) {
      json.push({
        id: id,
        type: type,
        labelValue: value,
        isRequired: false,
        options: options,
      });
      $('[name="regp_custom_field_json"]').val(JSON.stringify(json));
    }

    $('.ws-custom-input-field-label').trigger('keyup');
  }

  function makeId() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    for (var i = 0; i < 5; i++)
      text += possible.charAt(Math.floor(Math.random() * possible.length));
    return text;
  }

  if ($('[name="regp_custom_field_json"]').size() === 0) {
    return;
  }

  var json = JSON.parse($('[name="regp_custom_field_json"]').val());

  $.each(json, function (i, field) {
    addField(
      field.type,
      field.id,
      field.labelValue,
      true,
      !!field.isRequired,
      field.options
    );
  });

  $('.ws-custom-field-container').sortable({
    stop: function () {
      var json = [];

      $('.ws-custom-field-container li').each(function (i, el) {
        var id = $(el).attr('data-id');
        var checkbox = $('#ws-custom-input-required-' + id);
        var options = $(`textarea[data-id="${id}"].ws-custom-input-field-select-options`).val() || "";
        json.push({
          id: id,
          type: $(el).attr('data-type'),
          labelValue: $(`input[data-id="${id}"].ws-custom-input-field-label`).val() || "",
          isRequired: checkbox.length
            ? checkbox.prop('checked')
            : false,
          options: optionsFromString(options)
        });
      });
      $('[name="regp_custom_field_json"]').val(JSON.stringify(json));
    }
  });

  $('.ws-custom-field.button').click(function () {
    json = JSON.parse($('[name="regp_custom_field_json"]').attr('value'))
    addField($(this).attr('data-type'));
  });

  $(document).on('keyup', '.ws-custom-input-field-label', function () {
    var json = JSON.parse($('[name="regp_custom_field_json"]').attr('value'));
    var thisEl = $(this);

    $.each(json, function (i, field) {
      if (field.id == $(thisEl).attr('data-id')) {
        field.labelValue = $(thisEl).val();
      }
    });
    $('[name="regp_custom_field_json"]').val(JSON.stringify(json));
    $('.ws-custom-input-field-preview-input[data-id="' + $(thisEl).attr('data-id') + '"]').val($(thisEl).val());
  });

  $(document).on('keyup', '.ws-custom-input-field-select-options', function () {
    var json = JSON.parse($('[name="regp_custom_field_json"]').attr('value'));
    var thisEl = $(this);

    $.each(json, function (i, field) {
      if (field.id == $(thisEl).attr('data-id')) {
        field.options = optionsFromString($(thisEl).val());
      }
    });
    $('[name="regp_custom_field_json"]').val(JSON.stringify(json));
  });

  $(document).on('change', '.ws-custom-input-field-required', function () {
    var json = JSON.parse($('[name="regp_custom_field_json"]').attr('value'))
    var checkbox = $(this);
    $.each(json, function (i, field) {
      if (field.id == checkbox.attr('data-id')) {
        field.isRequired = checkbox.prop('checked');
      }
    });
    $('[name="regp_custom_field_json"]').val(JSON.stringify(json));
  });
});

jQuery(document).on('click', '.remove-custom-regfield', function (e) {
  e.preventDefault();
  if (confirm('Are you sure you want to delete this field?')) {
    var formId = jQuery(this).attr('data-id');
    jQuery('.ui-state-default[data-id="' + formId + '"]').fadeOut();
    var json = JSON.parse(jQuery('[name="regp_custom_field_json"]').val());

    jQuery(json).each(function (count) {
      if (json[count].id == formId) {
        json.splice(count, 1);
        jQuery('[name="regp_custom_field_json"]').val(JSON.stringify(json));
        return false;
      }
    });
  }
});
