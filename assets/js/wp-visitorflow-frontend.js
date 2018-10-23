// try {
	var wpvfURL = document.getElementById('wpvf-info').getAttribute('data-home-url');
	wpvfURL += '?wpvf_referrer=' + encodeURIComponent(document.referrer);
	wpvfURL += '&wpvf_page=' + encodeURIComponent(document.documentURI);
console.log(wpvfURL)
	var wpvfRequest = new XMLHttpRequest();
	wpvfRequest.open('GET',wpvfURL);
	wpvfRequest.send(null);
// } catch (e) {
// }
