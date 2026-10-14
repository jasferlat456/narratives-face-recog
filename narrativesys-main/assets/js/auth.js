(function () {
  var box = document.getElementById('authBox');
  var next = box.dataset.next || '';
  var alertEl = document.getElementById('authAlert');
  var forms = { login: document.getElementById('loginForm'), register: document.getElementById('registerForm') };
  var copy = {
    login: ['Welcome back.', 'Sign in to manage your bookings and photos.'],
    register: ['Create your account.', 'Register to book a session and get your photos online.']
  };
  var streams = { login: null, register: null };
  var modelsPromise = null;

  function stopCamera(mode) {
    if (streams[mode]) {
      streams[mode].getTracks().forEach(function (track) { track.stop(); });
      streams[mode] = null;
    }
    var video = document.querySelector('[data-face-video="' + mode + '"]');
    var capture = document.querySelector('[data-face-capture="' + mode + '"]');
    if (video) { video.pause(); video.srcObject = null; video.hidden = true; }
    if (capture) capture.disabled = true;
  }

  function stopAllCameras() { stopCamera('login'); stopCamera('register'); }

  function closeFacePopup(mode) {
    var faceBox = document.querySelector('[data-face-box="' + mode + '"]');
    if (faceBox) { faceBox.classList.remove('is-open'); faceBox.hidden = true; }
    document.body.classList.remove('face-modal-open');
    stopCamera(mode);
  }

  document.querySelectorAll('[data-face-open]').forEach(function (button) {
    button.addEventListener('click', function () {
      var mode = button.dataset.faceOpen;
      var faceBox = document.querySelector('[data-face-box="' + mode + '"]');
      faceBox.hidden = false;
      faceBox.classList.add('is-open');
      document.body.classList.add('face-modal-open');
      var status = document.querySelector('[data-face-status="' + mode + '"]');
      if (status && !document.querySelector('[data-face-value="' + mode + '"]').value) {
        status.textContent = mode === 'login' ? 'Open the camera, then capture your face to verify your account.' : 'Open the camera, look directly at it, then capture your face.';
      }
    });
  });

  document.querySelectorAll('[data-face-close]').forEach(function (button) {
    button.addEventListener('click', function () { closeFacePopup(button.dataset.faceClose); });
  });

  function show(mode) {
    stopAllCameras();
    document.querySelectorAll('[data-face-box]').forEach(function (el) { el.hidden = true; el.classList.remove('is-open'); });
    document.body.classList.remove('face-modal-open');
    box.dataset.mode = mode;
    document.getElementById('authTitle').textContent = copy[mode][0];
    document.getElementById('authSub').textContent = copy[mode][1];
    document.title = (mode === 'login' ? 'Sign in' : 'Register') + ' — Narrative Studio PH';
    forms.login.hidden = mode !== 'login';
    forms.register.hidden = mode !== 'register';
    document.querySelectorAll('.auth-tabs button').forEach(function (b) {
      var on = b.dataset.tab === mode;
      b.classList.toggle('on', on);
      b.setAttribute('aria-selected', on);
    });
    clearErrors();
    var first = forms[mode].querySelector('input');
    if (first) first.focus();
  }

  document.querySelectorAll('[data-tab]').forEach(function (el) {
    el.addEventListener('click', function (e) { e.preventDefault(); show(el.dataset.tab); });
  });

  function clearErrors() {
    alertEl.hidden = true;
    document.querySelectorAll('.err').forEach(function (p) { p.textContent = ''; });
    document.querySelectorAll('input.invalid').forEach(function (i) { i.classList.remove('invalid'); });
  }

  function showErrors(form, data) {
    alertEl.textContent = data.error || 'Something went wrong. Please try again.';
    alertEl.hidden = false;
    Object.keys(data.errors || {}).forEach(function (field) {
      var p = form.querySelector('[data-err="' + field + '"]');
      var input = form.querySelector('[name="' + field + '"]');
      if (p) p.textContent = data.errors[field];
      if (input && input.type !== 'hidden') input.classList.add('invalid');
    });
  }

  function post(url, body) {
    return fetch(url, {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
      body: JSON.stringify(body)
    }).then(function (r) {
      return r.json().catch(function () { return { error: 'Unexpected server response.' }; })
        .then(function (data) { return { ok: r.ok, data: data }; });
    });
  }

  function formData(form) {
    var out = {};
    new FormData(form).forEach(function (v, k) { out[k] = v; });
    out.consent = !!form.querySelector('[name="consent"]:checked');
    var descriptor = out.face_descriptor;
    if (typeof descriptor === 'string' && descriptor.trim()) {
      try { out.face_descriptor = JSON.parse(descriptor); } catch (_) { out.face_descriptor = null; }
    } else {
      delete out.face_descriptor;
    }
    return out;
  }

  function busy(form, on) { form.querySelector('.auth-btn').disabled = on; }
  function fail(form) {
    busy(form, false);
    showErrors(form, { error: 'Could not reach the server. Check your connection and try again.' });
  }

  function loadFaceModels() {
    if (!window.faceapi) return Promise.reject(new Error('Face recognition library did not load. Check your internet connection.'));
    if (!modelsPromise) {
      var modelUrl = 'https://justadudewhohacks.github.io/face-api.js/models';
      modelsPromise = Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
        faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
        faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl)
      ]);
    }
    return modelsPromise;
  }

  document.querySelectorAll('[data-face-start]').forEach(function (button) {
    button.addEventListener('click', function () {
      var mode = button.dataset.faceStart;
      var video = document.querySelector('[data-face-video="' + mode + '"]');
      var status = document.querySelector('[data-face-status="' + mode + '"]');
      var capture = document.querySelector('[data-face-capture="' + mode + '"]');
      var descriptorInput = document.querySelector('[data-face-value="' + mode + '"]');
      button.disabled = true;
      status.textContent = 'Loading face recognition and camera...';
      Promise.all([loadFaceModels(), navigator.mediaDevices && navigator.mediaDevices.getUserMedia
        ? navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
        : Promise.reject(new Error('Camera access requires HTTPS or localhost and a supported browser.'))
      ]).then(function (results) {
        streams[mode] = results[1];
        video.srcObject = streams[mode];
        video.hidden = false;
        return video.play();
      }).then(function () {
        capture.disabled = false;
        status.textContent = 'Camera is ready. Keep your face centered and use good lighting.';
      }).catch(function (err) {
        if (streams[mode]) stopCamera(mode);
        status.textContent = err && err.message ? err.message : 'Could not open camera. Allow camera permission and try again.';
      }).finally(function () { button.disabled = false; });
      if (descriptorInput) descriptorInput.value = '';
    });
  });

  document.querySelectorAll('[data-face-capture]').forEach(function (button) {
    button.addEventListener('click', function () {
      var mode = button.dataset.faceCapture;
      var video = document.querySelector('[data-face-video="' + mode + '"]');
      var status = document.querySelector('[data-face-status="' + mode + '"]');
      var input = document.querySelector('[data-face-value="' + mode + '"]');
      button.disabled = true;
      status.textContent = 'Detecting your face...';
      loadFaceModels().then(function () {
        return faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
          .withFaceLandmarks().withFaceDescriptor();
      }).then(function (result) {
        if (!result) throw new Error('No clear face detected. Face the camera and try again.');
        input.value = JSON.stringify(Array.from(result.descriptor));
        status.textContent = 'Face captured successfully. You can continue.';
        status.classList.add('ready');
        closeFacePopup(mode);
      }).catch(function (err) {
        status.textContent = err && err.message ? err.message : 'Could not capture face. Please try again.';
        button.disabled = false;
      });
    });
  });

  forms.login.addEventListener('submit', function (e) {
    e.preventDefault(); clearErrors();
    var f = forms.login, body = formData(f);
    body.next = next;
    busy(f, true);
    post('auth/login.php', body).then(function (res) {
      if (res.ok) { stopAllCameras(); window.location.href = res.data.redirect || 'booking.php'; return; }
      busy(f, false); showErrors(f, res.data);
      if (res.data && res.data.errors && res.data.errors.face_descriptor) {
        var loginFaceBox = document.querySelector('[data-face-box="login"]');
        loginFaceBox.hidden = false; loginFaceBox.classList.add('is-open'); document.body.classList.add('face-modal-open');
      }
    }).catch(function () { fail(f); });
  });

  forms.register.addEventListener('submit', function (e) {
    e.preventDefault(); clearErrors();
    var f = forms.register, body = formData(f);
    if (!Array.isArray(body.face_descriptor) || body.face_descriptor.length !== 128) {
      showErrors(f, { error: 'Please register your face before creating your account.', errors: { face_descriptor: 'Open the camera and capture your face first.' } });
      return;
    }
    busy(f, true);
    post('auth/register.php', body).then(function (res) {
      if (!res.ok) { busy(f, false); showErrors(f, res.data); return; }
      return post('auth/login.php', { email: body.email, password: body.password, face_descriptor: body.face_descriptor, next: next }).then(function (r2) {
        if (r2.ok) { stopAllCameras(); window.location.href = r2.data.redirect || 'booking.php'; return; }
        busy(f, false); show('login');
        alertEl.textContent = 'Account created. Please sign in.'; alertEl.hidden = false;
      });
    }).catch(function () { fail(f); });
  });

  window.addEventListener('beforeunload', stopAllCameras);
  show(box.dataset.mode === 'register' ? 'register' : 'login');
})();
