
var ajaxController = {
	
	/**
	 * AJAX-Request
	 * @param addr string Ziel-Adresse
	 * @param r DOM Ziel-Element
	 * @param post obj POST-Daten
	 * @param errorout Fehler im Ziel-Element ausgeben
	 */
	call: function(addr, r, post, errorout) {		
		
		if(!addr) {
			return false;
		}
		
		// Lade-Anzeige im Ziel-Element anzeigen
		if(r) {
			$(r).html('<div class="center"><img src="img/ajax.gif" /></div>');
		}
		
		// Buttons deaktivieren
		$('input[type=submit], input[type=button], button').prop('disabled', true);
		
		
		// get oder post
		if(post == false) {
			var ajaxtype = 'get';
			post = '';
		}
		else var ajaxtype = 'post';
		
		// &ajax an die Adresse anhängen
		if(addr.indexOf('&ajax') == -1 && addr.indexOf('?ajax') == -1) {
			if(addr.indexOf('?') != -1) addr += '&ajax';
			else addr += '?ajax';
		}
		
		// Request absetzen
		$.ajax({
			type: ajaxtype,
			url: addr,
			data: post,
			
			success: function(data, status, xhr){
				// Fehlermeldung
				if(data.error) {
					if(errorout && r) {
						$(r).html('<div class="error">'+data.error+'</div>');
					}
					else {
						alert(data.error);
					}
				}
				else {
					// Inhalt ausgeben
					if(r) {
						$(r).html(data.content);
					}
					
					// JavaScript ausführen
					if(data.script != '') {
						eval(data.script);
					}
				}
				
				// Buttons reaktivieren
				$('input[type=submit], input[type=button], button').prop('disabled', false);
			},
			error: function(e, msg) {
				// Fehlermeldung
				if(e.status != 0) {
					var c = e.responseText;
					
					// Zeilenumbrüche und Fettschrift entfernen
					c = c.replace(/<br \/>/g, "\n");
					c = c.replace(/<(|\/)b>/g, '');
					
					if(errorout && r) {
						// HTML nicht interpretieren
						c = c.replace(/&/g, '&amp;');
						c = c.replace(/</g, '&lt;');
						c = c.replace(/>/g, '&gt;');
						c = c.replace(/"/g, '&quot;');
						
						$(r).html('<div class="error">Es ist ein Fehler aufgetreten!<br /><br />Fehlermeldung: '+msg+' '+e.status+'><br />Adresse: '+addr+'<br />Ausgabe: '+c+'</div>');
					}
					else {
						alert("Es ist ein Fehler aufgetreten!\nFehlermeldung: "+msg+' '+e.status+"\nAdresse: "+addr+"\nAusgabe: "+c);
					}
				}
				
				// Buttons reaktivieren
				$('input[type=submit], input[type=button], button').prop('disabled', false);
			}
		});
	},
	
	
	/**
	 * Klick-Event auf einen AJAX-Link abfangen
	 */
	clickLink: function(el, e) {
		// bestätigen
		if($(el).data('confirm')) {
			if(!window.confirm($(el).data('confirm'))) {
				e.preventDefault();
				return false;
			}
		}
		
		// Ziel
		var target = el.parentNode;
		
		if($(el).data('target') && $(el).data('target') != 'this') {
			target = $($(el).data('target'));
		}
		
		// AJAX-Abfrage
		ajaxController.call(
			$(el).attr('href'),
			target,
			false,
			!$(el).data('error')
		);
		
		// normales Klicken verhindern
		e.preventDefault();
	},
	
	/**
	 * Submit-Event eines Formulars abfangen
	 */
	submitForm: function(el, e) {
		// Ziel
		var target = el.parentNode;
		
		if($(el).data('target') && $(el).data('target') != 'this') {
			target = $($(el).data('target'));
		}
		
		// AJAX-Abfrage
		ajaxController.call(
			$(el).attr('action'),
			target,
			$(el).serialize(),
			!$(el).data('error')
		);
		
		// normales Abschicken verhindern
		e.preventDefault();
	}
	
}

/*
 * Shortcut-Funktionen
 */

/**
 * Weiterleitung
 */
function url(a) {
	document.location.href = a;
}



/*
 * Initialisierungen beim Laden der Seite
 */

$(document).ready(function() {
	
	// AJAX einrichten
	$.ajaxSetup({
		dataType: 'json'
	});
	
	$(document).ajaxStart(function(){
		$('#ajaxload').show();
	}).ajaxComplete(function() {
		$('#ajaxload').hide();
	});
	
	
	// AJAX-Links
	$(document).on('click', '.ajax', function(e) {
		ajaxController.clickLink(this, e);
	});
	
	
	// AJAX-Formulare
	$(document).on('submit', '.ajaxform', function(e) {
		ajaxController.submitForm(this, e);
	});
	
	// Toggle-Icons
	$(document).on('click', '.folder_toggle', function(e) {
		
		// Ziel-ID ermitteln
		var id = $(this).data('id');
		
		if(id === null) {
			return false;
		}
		
		var target = $('#folder'+id);
		
		var toggle = $(this).data('toggle') ? 0 : 1;
		$(this).data('toggle', toggle);
		
		// Grafik ändern
		$(this).find('img').attr('src', (toggle ? 'img/ordner-offen.png' : 'img/ordner.png'));
		
		
		// Content schon geladen
		if(target.data('loaded')) {
			ajaxController.call('index.php?p=files&sp=toggle', false, {'id':id, 'toggle':toggle}, false);
		}
		// noch nicht geladen
		else {
			target.data('loaded', 1);
			ajaxController.call('index.php?p=files&sp=toggle&load', target, {'id':id, 'toggle':toggle}, false);
		}
		
		target.slideToggle(300);
		
		e.preventDefault();
	});
	
	// Wurzekordner ändern
	$('#select_topfolder').change(function() {
		url('index.php?p=files&top='+$(this).val());
	});
	
});