<!-- @see https://developers.google.com/identity/sign-in/web/build-button -->
<div disabled class=google>
  Aanmelden via Google
</div>
<script src="https://apis.google.com/js/api:client.js"></script>
<script>
  addEventListener('load',  e => {
    const button = document.querySelector('div.google')
    const form = document.querySelector('form')
    gapi.load('auth2', () => {
      gapi.auth2.init({
        client_id: '784232209684-ltht7catv8fm30n2p5ab2ga3ndrf0ngs.apps.googleusercontent.com',
        cookiepolicy: 'single_host_origin',
        scope: 'profile email'
      }).attachClickHandler(button, {}, googleUser => {
        const profile = googleUser.getBasicProfile()
        form.email.value = profile.getEmail()
        form.first_name.value = profile.getGivenName()
        form.last_name.value = profile.getFamilyName()
        form.submit()
      })
    })
  })
</script>
