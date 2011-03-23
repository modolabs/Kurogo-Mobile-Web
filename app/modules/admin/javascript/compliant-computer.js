function createFormFieldListItem(fieldData) {
    var listClass='';
    switch (fieldData.type) {
        case 'checkbox':
            listClass='checkitem';
            break;
        case 'paragraph':
            listClass='tallfield';
            break;
    }

    var section = typeof fieldData.section == 'undefined' ? '' : fieldData.section;
    var li = $('<li>').attr('class', listClass);

    if (fieldData.label) {
        li.append('<label>' + fieldData.label + ':</label>');
    }
    
    fieldData.value = 'value' in fieldData ? fieldData.value : '';
    
    switch (fieldData.type) {
    
        case 'time':
            li.append($('<input/>').attr('type','text').attr('name', fieldData.key).attr('section', section).attr('config', fieldData.config).attr('value', fieldData.value).attr('class','timeData'));
            li.append('seconds');
            break;
        case 'file':
            li.append(createSelectBox(fileListTypes(), fieldData.constant).attr('class','filePrefix'));
            li.append($('<input/>').attr('type','text').attr('name', fieldData.key).attr('section', section).attr('config', fieldData.config).attr('value', fieldData.value).attr('class','fileData'));
            break;
        case 'number':
            li.append($('<input/>').attr('type','text').attr('name', fieldData.key).attr('section', section).attr('config', fieldData.config).attr('value', fieldData.value));
            break;
        case 'password':
        case 'text':
            li.append($('<input/>').attr('type',fieldData.type).attr('name', fieldData.key).attr('section', section).attr('config', fieldData.config).attr('value', fieldData.value));
            break;
        case 'checkbox':
            li.append($('<input/>').attr('type','hidden').attr('name', fieldData.key).attr('section', section).attr('config', fieldData.config).attr('value', '0'));
            li.append($('<input/>').attr('type',fieldData.type).attr('name', fieldData.key).attr('section', section).attr('config', fieldData.config).attr('value', '1').attr('checked', parseInt(fieldData.value) ? 'checked':''));
            break;
        case 'select':
            var options = 'options' in fieldData ? fieldData.options : [];
            li.append(createSelectBox(options, fieldData.value).attr('name',fieldData.key).attr('section', section).attr('config', fieldData.config));
            break;
        case 'paragraph':
            li.append($('<textarea>'+(fieldData.value ? fieldData.value : '')+'</textarea>').attr('name',fieldData.key).attr('rows','5').attr('section', section).attr('config', fieldData.config));
            break;
        case 'label':
            li.append(fieldData.value);
            break;
    }

    if (fieldData.description) {
        li.append('<span class="helptext">' + fieldData.description + '</span>');
    }

    return li;
}

function createSelectBox(options, selected) {
    var select = $('<select>');
    $.each(options, function(k,v) {
        select.append($("<option>" + v + "</option>").attr('value', k).attr('selected', selected==k ? 'selected' :''));
    });
    return select;
}

function fileListTypes() {  
    return {'':'-','FULL_URL_BASE':'FULL_URL_BASE','LOG_DIR':'LOG_DIR','LIB_DIR':'LIB_DIR','CACHE_DIR':'CACHE_DIR','DATA_DIR':'DATA_DIR','SITE_DIR':'SITE_DIR','ROOT_DIR':'ROOT_DIR'};
}


function makeAPICall(type, module, command, data, callback) {
    var url = URL_BASE + 'rest/' + module + '/' + command;
    $.ajax({
        type: type,
        url: url,
        data: data, 
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                alert(data.error.message);
               return;
            }
                    
            if (callback) {
                callback(data.response);
            }
        }
    });
}