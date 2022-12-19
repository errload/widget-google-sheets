// window.onload = function () {
$(document).ready(function () {
  var oauth_scripts = document.querySelectorAll('.amocrm_oauth_doc');
  window.addEventListener('message', receiveOAuthMessage, false);

  oauth_scripts.forEach(function (oauth_script) {
    var client_id = oauth_script.dataset.clientId,
      state = oauth_script.dataset.state || Math.random().toString(36).substring(2),
      compact = oauth_script.dataset.compact === 'true' || false,
      title = oauth_script.dataset.title || 'Авторизоваться через amoCRM',
      mode = oauth_script.dataset.mode || 'popup',
      name = oauth_script.dataset.name || null,
      description = oauth_script.dataset.description || null,
      logo = oauth_script.dataset.logo || null,
      redirect_uri = oauth_script.dataset.redirect_uri || null,
      secrets_uri = oauth_script.dataset.secrets_uri || null,
      scopes = oauth_script.dataset.scopes || null,
      final_scopes,
      colors = {
        'default': '#339DC7',
        'blue': '#1976D2',
        'violet': '#6A1B9A',
        'green': '#388E3C',
        'orange': '#F57F17',
        'red': '#D84315',
      },
      border_background_colors = {
        'default': '#006F9F',
        'blue': '#004BA0',
        'violet': '#38006B',
        'green': '#00600F',
        'orange': '#BC5100',
        'red': '#9F0000',
      },
      border_background = border_background_colors[oauth_script.dataset.color] || border_background_colors.default,
      color = colors[oauth_script.dataset.color] || colors.default,
      className = oauth_script.dataset.className || 'amocrm-oauth';

    if ((!client_id || !oauth_script) && !(name && description && redirect_uri && secrets_uri && scopes)) {
      console.error('No client_id or client_secret or script tag or metadata');
      return;
    }

    var url_parser = document.createElement('a');
    var button = document.createElement('div');
    var button_html = [
      '<div style="padding-left: 2px; height: 100%; display: flex; background: ' + border_background + '; align-items: center;">',
      '<svg width="43" height="13" viewBox="0 0 43 13" fill="none" xmlns="http://www.w3.org/2000/svg">',
      '<path d="M15.4542 6.72519C15.7446 6.72519 15.8899 7.09709 15.8899 7.84087C15.8578 8.36991 15.6882 8.88139 15.3977 9.32437C15.0439 9.91638 14.5987 10.4484 14.0787 10.9008C13.5376 11.3781 12.9302 11.7743 12.2756 12.0771C11.6716 12.3707 11.0107 12.528 10.3394 12.5379C9.48434 12.5379 8.91151 11.786 8.62113 10.2823C8.20289 10.9673 7.63455 11.5479 6.95922 11.9801C6.1357 12.4631 5.18947 12.6949 4.23647 12.647C3.72954 12.6689 3.22359 12.585 2.75072 12.4006C2.27786 12.2162 1.84838 11.9353 1.48953 11.5758C1.15318 11.2385 0.889435 10.8355 0.714601 10.3919C0.539766 9.94839 0.457551 9.47358 0.473034 8.9969C0.473034 6.24822 1.38465 4.0816 3.20788 2.49706C3.83624 1.92106 4.57252 1.47589 5.37398 1.18739C6.05973 0.974815 6.77385 0.868491 7.49169 0.872091C8.12102 0.851694 8.74533 0.990724 9.30685 1.27631C9.53273 0.807416 9.79087 0.572968 10.0813 0.572968C10.3857 0.58302 10.6852 0.65359 10.9621 0.780577C11.2391 0.907564 11.4881 1.08844 11.6948 1.31269C12.1184 1.65106 12.391 2.14374 12.4531 2.683C12.1626 3.34181 11.8312 3.98173 11.4608 4.59901C11.3118 5.63492 11.2471 6.68123 11.2672 7.7277C11.2672 9.0751 11.4366 9.7488 11.7755 9.7488C12.1949 9.7488 13.2114 8.90798 14.825 7.22641C15.1153 6.8923 15.3251 6.72519 15.4542 6.72519ZM4.03076 8.19656C4.03076 9.44157 4.4906 10.0641 5.41028 10.0641C6.39449 10.0479 7.2093 9.52244 7.85472 8.48763C8.10283 8.08052 8.29035 7.6393 8.41134 7.17796C8.38481 5.61185 8.54732 4.04829 8.89542 2.52131C7.58889 2.77985 6.39572 3.4413 5.48289 4.41307C5.00143 4.91489 4.62494 5.50812 4.37556 6.15787C4.12619 6.80764 4.00895 7.50076 4.03076 8.19656Z" fill="white"/>',
      '<path d="M26.1278 5.10855C26.1278 4.56959 25.8212 4.30011 25.2081 4.30011C24.7886 4.30011 24.3207 4.70433 23.8044 5.51277C23.2908 6.32227 22.9146 7.21148 22.6911 8.14425V8.19276C22.695 8.76786 22.7806 9.33952 22.9452 9.89048C23.0776 10.2831 23.1629 10.6902 23.1993 11.1031C23.1993 11.6421 22.5136 12.1811 21.1421 12.72C20.9654 12.7937 20.7768 12.8348 20.5855 12.8413C20.3957 12.8464 20.2097 12.7868 20.058 12.6723C19.9063 12.5577 19.7979 12.395 19.7505 12.2107C19.5778 11.6579 19.5 11.0798 19.5206 10.5009C19.5206 9.78133 19.5408 9.10763 19.5811 8.47976C19.6214 7.85183 19.6618 7.26169 19.7021 6.70927C19.7424 6.1703 19.7828 5.69332 19.8231 5.27832C19.8635 4.86332 19.8836 4.52781 19.8836 4.27181C19.8836 3.73285 19.6336 3.46337 19.1334 3.46337C18.6332 3.46337 18.0523 4.04949 17.3908 5.22173C16.7293 6.39399 16.3985 7.32367 16.3985 8.01084C16.3926 8.65434 16.4781 9.29544 16.6526 9.91474C16.7851 10.3074 16.8704 10.7145 16.9068 11.1274C16.9068 11.6663 16.221 12.2053 14.8496 12.7443C14.6728 12.8179 14.4842 12.859 14.2929 12.8655C14.1032 12.8699 13.9176 12.8101 13.7661 12.6956C13.6146 12.5812 13.506 12.4189 13.4579 12.235C13.2771 11.6049 13.1995 10.9497 13.228 10.2947C13.1894 8.63299 13.2784 6.97078 13.4943 5.32278C13.743 3.80135 14.3033 2.34799 15.14 1.05421C15.2278 0.87075 15.3603 0.712387 15.5253 0.593711C15.6902 0.475037 15.8823 0.399865 16.0839 0.375122C16.6217 0.375122 17.1138 0.811681 17.5602 1.6848C17.6771 1.88511 17.7744 2.09628 17.8506 2.31538C18.1604 1.87461 18.5694 1.51317 19.0446 1.26035C19.5197 1.00752 20.0476 0.870435 20.5855 0.860187C21.2698 0.856701 21.932 1.10233 22.4491 1.5514C22.7068 1.75948 22.9136 2.02397 23.0534 2.32458C23.1932 2.62518 23.2625 2.95391 23.2558 3.28551V3.55229C23.6699 2.98259 24.2123 2.51878 24.8389 2.19852C25.4655 1.87825 26.1586 1.71056 26.8619 1.70905C27.2212 1.68334 27.582 1.7302 27.923 1.84685C28.2639 1.96351 28.5779 2.14757 28.8465 2.38814C29.0675 2.60598 29.2415 2.86683 29.3581 3.15463C29.4746 3.44244 29.5311 3.7511 29.5242 4.06162C29.5293 4.663 29.4807 5.26365 29.379 5.85636C29.2822 6.39533 29.1732 6.89656 29.0522 7.36005C28.9312 7.82355 28.8223 8.23993 28.7255 8.60909C28.6429 8.88512 28.5942 9.17014 28.5803 9.45795C28.5803 9.66005 28.6448 9.76115 28.7739 9.76115C29.1159 9.71387 29.4318 9.55136 29.6694 9.30034C30.0244 8.99311 30.3793 8.6576 30.7343 8.29381L31.7145 7.28728C32.0237 6.97201 32.2321 6.81434 32.3397 6.81434C32.5656 6.81434 32.6785 7.08385 32.6785 7.62279C32.6785 8.75463 32.1098 9.8541 30.9723 10.9213C30.0191 11.9143 28.7163 12.4946 27.3419 12.5381C26.1802 12.5381 25.5994 11.479 25.5994 9.36092C25.6232 8.61883 25.7123 7.88029 25.8656 7.15387C26.0093 6.48058 26.097 5.79643 26.1278 5.10855Z" fill="white"/>',
      '<path d="M39.4383 10.4037C38.895 11.104 38.1996 11.671 37.4049 12.0619C36.6103 12.4527 35.7372 12.657 34.852 12.6593C33.0691 12.6593 31.734 12.2025 30.8465 11.2889C30.4086 10.8401 30.0667 10.3064 29.842 9.72037C29.6173 9.13442 29.5144 8.50865 29.5396 7.88136C29.4885 6.05253 30.138 4.27343 31.3548 2.90946C31.953 2.22568 32.6955 1.68379 33.5285 1.32304C34.3615 0.962295 35.2641 0.791725 36.171 0.823684C36.9121 0.796597 37.6514 0.911851 38.3492 1.16323C39.3012 1.55128 39.7771 1.91508 39.7771 2.25462C39.7771 2.5241 39.1116 2.65884 37.7805 2.65884C37.159 2.64752 36.5417 2.76439 35.9671 3.00221C35.3926 3.24003 34.873 3.59371 34.4406 4.04128C33.9958 4.49693 33.6476 5.03796 33.4168 5.63187C33.1859 6.22578 33.0773 6.86032 33.0974 7.49734C33.0765 8.16644 33.3052 8.81926 33.7387 9.32847C33.9688 9.58043 34.2525 9.77739 34.5688 9.90474C34.8851 10.032 35.2259 10.0865 35.566 10.0642C36.248 10.0892 36.9232 9.92112 37.5142 9.57909C36.9599 9.21553 36.5099 8.71332 36.2085 8.12212C35.9071 7.53087 35.7649 6.87105 35.7959 6.20791C35.7849 5.418 36.0848 4.65556 36.6309 4.08574C36.9058 3.77365 37.2452 3.52522 37.6255 3.3577C38.0058 3.19018 38.4179 3.10757 38.8333 3.11562C39.7287 3.11562 40.3701 3.36219 40.7574 3.85534C41.1628 4.41381 41.3676 5.09345 41.3382 5.78348C41.322 6.64129 41.1326 7.48696 40.7816 8.26941H40.9268C41.6066 8.26399 42.2594 8.00125 42.754 7.53373C42.9476 7.33973 43.113 7.16994 43.2502 7.02446C43.2995 6.96288 43.3605 6.91186 43.4298 6.87437C43.499 6.83688 43.5751 6.81367 43.6536 6.80615C43.8795 6.80615 43.9924 7.07565 43.9924 7.6146C44.0231 8.02632 43.9607 8.43973 43.8098 8.82399C43.6589 9.20818 43.4235 9.55337 43.1211 9.83378C42.592 10.2949 41.9144 10.5491 41.2132 10.5492C40.6182 10.5581 40.0239 10.5093 39.4383 10.4037ZM39.2205 7.57419C39.4791 7.04527 39.6169 6.46535 39.6239 5.87644C39.6239 5.33748 39.4464 5.068 39.0914 5.068C38.9462 5.068 38.8252 5.1731 38.7284 5.3833C38.6366 5.57243 38.5871 5.77934 38.5832 5.98963C38.5745 6.28593 38.6266 6.58088 38.7362 6.85624C38.8458 7.13157 39.0107 7.38142 39.2205 7.59034V7.57419Z" fill="white"/>',
      '</svg>',
      '</div>',
    ];

    if (!compact) {
      var text_style = [
        'display: inline-block;',
        'padding-left: 17px;',
        'padding-right: 18px;',
        'font-family: sans-serif;',
        'font-weight: bold;',
        'font-size: 14px;',
        'line-height: 18px;',
        'text-align: center;',
        'letter-spacing: 1px;',
        'text-transform: uppercase;',
        'color: #FFFFFF;',
      ].join('');

      button_html.push([
        '<span style="' + text_style + '">' + title + '</span>'
      ])
    }

    button.className = className;
    button.dataset.client_id = client_id;
    button.innerHTML = button_html.join('');

    button.style = [
      'display: inline-flex',
      'align-items: center',
      'background: ' + color,
      'color: #fff',
      'border: 1px solid ' + border_background,
      'cursor: pointer',
      'font-family: Roboto,Helvetica,Arial,sans-serif',
      'font-size: 12px',
      'line-height: 13px',
      '-webkit-font-smoothing: antialiased',
      'text-rendering: optimizeLegibility',
      'height: 45px',
    ].join(';');

    oauth_script.parentNode.insertBefore(button, oauth_script);

    button.onclick = function () {
      var url_array = [
        'https://www.amocrm.ru/oauth/',
        '?state=', state,
        '&mode=', mode
      ];

      if (client_id) {
        url_array.push('&client_id=', client_id);
      } else if (name && description && redirect_uri && secrets_uri && scopes) {
        url_array.push('&name=', name);
        url_array.push('&description=', description);
        url_array.push('&redirect_uri=', redirect_uri);
        url_array.push('&secrets_uri=', secrets_uri);
        url_array.push('&logo=', logo);
        final_scopes = scopes.split(',');
        final_scopes.forEach(function(scope) {
          url_array.push('&scopes[]=', scope)
        });
      }

      centerAuthWindow(
        url_array.join(''),
        'Предоставить доступ для интеграции'
      );
    };

    var centerAuthWindow = function (url, title) {
      var w = 750;
      var h = 580;
      var dual_screen_left = window.screenLeft !== undefined ? window.screenLeft : screen.left;
      var dual_screen_top = window.screenTop !== undefined ? window.screenTop : screen.top;

      var width = window.innerWidth
        ? window.innerWidth
        : (document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width);

      var height = window.innerHeight
        ? window.innerHeight
        : (document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height);

      var left = ((width / 2) - (w / 2)) + dual_screen_left;
      var top = ((height / 2) - (h / 2)) + dual_screen_top;

      var new_window = window.open(url, title, 'scrollbars, status, resizable, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

      if (window.focus) {
        new_window.focus();
      }
    };
  });
});

function receiveOAuthMessage(event) {
  var oauth_scripts = document.querySelectorAll('.amocrm_oauth');

  oauth_scripts.forEach(function (oauth_script) {
    if (event.data.client_id && oauth_script.dataset.clientId && event.data.client_id === oauth_script.dataset.clientId) {
      oauth_script.dataset.error = event.data.error;
      if (oauth_script.dataset.errorCallback) {
        try {
          var errorCallback = eval(oauth_script.dataset.errorCallback);
          if (typeof errorCallback === 'function') {
            errorCallback(event.data);
          }
        } catch (e) {
          // noop
        }
      }
    }
  });
}

// Use:
//   <script
//   class="amocrm_oauth"
//   charset="utf-8"
//   data-client-id="xxxx"
//   data-title="Button"
//   data-compact="false"
//   data-class-name="className"
//   data-color="red"
//   data-state="state"
//   data-error-callback="functionName"
//   data-mode="popup"
//   src="https://www.amocrm.ru/auth/button.js"
// ></script>
//
// <script
//   class="amocrm_oauth"
//   charset="utf-8"
//   data-name="Integration name"
//   data-description="Integration description"
//   data-redirect_uri="https://example.com"
//   data-secrets_uri="https://example.com/secrets"
//   data-logo="https://example.com/amocrm_logo.png"
//   data-scopes="crm,notifications"
//   data-title="Button"
//   data-compact="false"
//   data-class-name="className"
//   data-color="red"
//   data-state="state"
//   data-mode="post_message"
//   src="https://www.amocrm.ru/auth/button.js"
// ></script>
