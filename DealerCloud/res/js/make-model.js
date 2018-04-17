var parseXml;
if (window.DOMParser) {
    parseXml = function (xmlStr) {
        return (new window.DOMParser()).parseFromString(xmlStr, "text/xml");
    };
} else if (typeof window.ActiveXObject != "undefined" && new window.ActiveXObject("Microsoft.XMLDOM")) {
    parseXml = function (xmlStr) {
        var xmlDoc = new window.ActiveXObject("Microsoft.XMLDOM");
        xmlDoc.async = "false";
        xmlDoc.loadXML(xmlStr);
        return xmlDoc;
    };
} else {
    parseXml = function () { return null; }
}

function populateModels(models) {
    var modSel = $('#model');
    modSel.empty().append(models);
}

function loadModels(a_make) {
    // send request to get models list
    var request = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
    request += "<request method=\"vehicle.list_models\">";
    request += "<token>" + awe_options.aweAPIKey + "</token>";
    request += "<make>" + a_make + "</make>";
    // request += "<dealer_id>" + awe_options.appName + "</dealer_id>";
    request += "</request>";

    // create web client
    var client = new XMLHttpRequest();

    // server response handler
    client.onload = function (e) {
        var xml = parseXml(this.responseText);
        if (this.status != 200 || xml === null || xml.documentElement === null) {
            return;
        }

        var status = xml.documentElement.attributes.item(0).nodeValue;
        if (status == 'fail') {
            return;
        }

        var lstModels = xml.documentElement.getElementsByTagName("models");
        if (lstModels.length < 1) {
            return;
        }

        var model = lstModels.item(0).getElementsByTagName("model");
        if (model.length < 1) {
            return;
        }

        // add each make to our list
        var models = "<option value=''></option>";
        for (var i = 0; i < model.length; i++) {
            models += "<option value='" + model.item(i).textContent + "'>" + model.item(i).textContent + "</option>";
        }

        populateModels(models);
    };

    // send request to server
    client.open("POST", awe_options.aweAPIURL, true);
    client.setRequestHeader("Content-Type", "text/xml;charset=UTF-8");
    client.send(request);
}

function getModels() {
    var make = $("#make").val();
    var modSel = $('#model');
    modSel.empty().append("<option value=''>Loading...</option>");
    loadModels(make);
}
