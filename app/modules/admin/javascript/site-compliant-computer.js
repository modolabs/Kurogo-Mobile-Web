var adminType='site';
var KUROGO_LOCAL_VERSION;
var KUROGO_CURRENT_VERSION;
$(document).ready(function() {

    reloadSection();    
    
    $('#adminForm').submit(function(e) {
        var params = { 'v':1, 'type':'site', 'section':adminSection, 'data':{}}
        var re;

        params.data[adminSection] = {};
        
        var data = {};
        
        $('#adminForm [section]').map(function() {
            if ( !this.disabled &&  (this.type !='checkbox' || this.checked)) {

                if (re = $(this).attr('name').match(/(.*)\[(.*)\]/)) {
                    if (typeof data[re[1]]=='undefined') {
                        data[re[1]] = {}
                    }
                    data[re[1]][re[2]] = $(this).val();                    
                } else {
                    data[$(this).attr('name')] = $(this).val();
                }
            }
        });
        
        if (adminSubsection) {
            params.subsection = adminSubsection;
            params.data[adminSection][adminSubsection] = data;
        } else {
            params.data[adminSection] = data;
        }
        
        makeAPICall('POST','admin','setconfigdata', params, function() { 
            showMessage('Configuration saved');
            reloadSection();
        });
        return false;
    });
    
    $('nav a[section]').click(function(e) {
        var section = $(this).attr('section');
        if (adminSection != section) {
            adminSection = section;
            adminSubsection = '';
            $('nav ul li a[class=current]').attr('class','');
            $(this).attr('class','current');
            reloadSection();
        }
        return false;
    });
});

function checkVersion() {
    makeAPICall('GET', 'admin','checkversion', { 'v':1}, processCheckVersion);
}

function processCheckVersion(data) {
    KUROGO_LOCAL_VERSION = data.local;
    KUROGO_CURRENT_VERSION = data.current;
    
    var li = $('<li />');
    li.append('<label>Kurogo Version</label>');
    if (KUROGO_LOCAL_VERSION != KUROGO_CURRENT_VERSION) {
        li.append('<div class="infotext error">Your version of Kurogo is not the most recent version. The most recent version is <b>' + KUROGO_CURRENT_VERSION +'</b>. Your version is <b>' + KUROGO_LOCAL_VERSION + '</b>. Please visit <a href="http://modolabs.com/kurogo">http://modolabs.com/kurogo</a>.</div>');
    } else {
        li.append('<div class="infotext">Your version of Kurogo is up to date (' + KUROGO_LOCAL_VERSION +')</div>');
    }

    $('#adminFields').append(li);
}

function reloadSection() {
    makeAPICall('GET','admin','getconfigdata', { 'v':1,'type':'site','section':adminSection}, processAdminSectionData);
}

function selectSubsection(subsection, subsectionData) {
    $('#sectionDescription').html(subsectionData.description);
    $("#adminFields").html('');
    $.each(createFormSectionListItems(subsectionData.section, subsectionData), function(k,element) {
        $("#adminFields").append(element);
    });
    adminSubsection = subsection;
}

function processAdminSectionData(data) {
    $('#sectionTitle').html(data.title);
    $('#sectionDescription').html(data.description);
    $('#section').val(data.section);
    $("#adminFields").html('');
    $("#adminSections").html("");
    if (data.sectiontype=='sections') {
        $("#adminSections").html('').show();
        var first = true;
        var sectionCount = 0;
        $.each(data.sections, function(subsection, subsectionData) {
            sectionCount++;
            var li = $('<li />').append('<a href="?section='+adminSection+'&subsection='+subsection+'">'+subsectionData.title+'</a>').addClass(subsectionData.type).click(function() {
                $('#adminSections .selected').removeClass('selected');
                $(this).addClass('selected');
                selectSubsection(subsection, subsectionData);
                return false;
            });
            if ((!adminSubsection && first) || (adminSubsection==subsection)) {
                li.addClass('selected');
                selectSubsection(subsection, subsectionData);
                first = false;
            }
            $("#adminSections").append(li);
        });
        if (sectionCount<2) {
            $("#adminSections").hide();
        }
    } else {
        $("#adminSections").hide();
        $.each(createFormSectionListItems(data.section, data), function(k,element) {
            $("#adminFields").append(element);
        });
    }
    if (adminSection=='setup') {
        checkVersion();
    }
}
    
