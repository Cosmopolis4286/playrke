<template>
  <div class="prefs-tab">

    <!-- Row 1: General + Notificaciones -->
    <div class="prefs-grid">

      <!-- General -->
      <div class="prefs-card">
        <div class="prefs-card-head">
          <span class="prefs-card-icon-wrap prefs-card-icon-wrap--blue">
            <i class="fas fa-sliders-h"></i>
          </span>
          <span class="prefs-card-title">General</span>
        </div>
        <div class="prefs-card-body">
          <div class="field-group">
            <label class="field-label">Idioma</label>
            <div class="field-select-wrap">
              <select v-model="prefsForm.language" class="field-select">
                <option value="es">🇻🇪  Español</option>
                <option value="en">🇺🇸  English</option>
                <option value="pt">🇧🇷  Português</option>
                <option value="fr">🇫🇷  Français</option>
              </select>
              <i class="fas fa-chevron-down field-select-arrow"></i>
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Zona Horaria</label>
            <div class="field-select-wrap">
              <select v-model="prefsForm.timezone" class="field-select">
                <option value="America/Caracas">Caracas (GMT-4)</option>
                <option value="America/Bogota">Bogotá (GMT-5)</option>
                <option value="America/Sao_Paulo">São Paulo (GMT-3)</option>
                <option value="America/Buenos_Aires">Buenos Aires (GMT-3)</option>
                <option value="America/Mexico_City">Ciudad de México (GMT-6)</option>
                <option value="America/New_York">Nueva York (GMT-5)</option>
                <option value="Europe/Madrid">Madrid (GMT+1)</option>
                <option value="Europe/London">Londres (GMT+0)</option>
              </select>
              <i class="fas fa-chevron-down field-select-arrow"></i>
            </div>
          </div>
        </div>
        <div class="prefs-card-footer">
          <button class="save-btn" @click="$emit('save', { language: prefsForm.language, timezone: prefsForm.timezone })">
            <i class="fas fa-check me-1"></i>Guardar
          </button>
        </div>
      </div>

      <!-- Notificaciones -->
      <div class="prefs-card">
        <div class="prefs-card-head">
          <span class="prefs-card-icon-wrap prefs-card-icon-wrap--amber">
            <i class="fas fa-bell"></i>
          </span>
          <span class="prefs-card-title">Notificaciones</span>
        </div>
        <div class="prefs-card-body">
          <div class="toggle-row" @click="prefsForm.notifications.email = !prefsForm.notifications.email">
            <div class="toggle-left">
              <span class="toggle-icon-wrap toggle-icon-wrap--blue"><i class="fas fa-envelope"></i></span>
              <div class="toggle-text">
                <span class="toggle-label">Correo electrónico</span>
                <span class="toggle-hint">Actualizaciones importantes en tu correo</span>
              </div>
            </div>
            <div :class="['toggle-switch', { 'toggle-switch--on': prefsForm.notifications.email }]">
              <div class="toggle-knob" />
            </div>
          </div>
          <div class="toggle-row" @click="prefsForm.notifications.push = !prefsForm.notifications.push">
            <div class="toggle-left">
              <span class="toggle-icon-wrap toggle-icon-wrap--purple"><i class="fas fa-mobile-alt"></i></span>
              <div class="toggle-text">
                <span class="toggle-label">Notificaciones Push</span>
                <span class="toggle-hint">Alertas en tiempo real en tu dispositivo</span>
              </div>
            </div>
            <div :class="['toggle-switch', { 'toggle-switch--on': prefsForm.notifications.push }]">
              <div class="toggle-knob" />
            </div>
          </div>
          <div class="toggle-row" @click="prefsForm.notifications.sms = !prefsForm.notifications.sms">
            <div class="toggle-left">
              <span class="toggle-icon-wrap toggle-icon-wrap--green"><i class="fas fa-sms"></i></span>
              <div class="toggle-text">
                <span class="toggle-label">SMS</span>
                <span class="toggle-hint">Alertas críticas por mensaje de texto</span>
              </div>
            </div>
            <div :class="['toggle-switch', { 'toggle-switch--on': prefsForm.notifications.sms }]">
              <div class="toggle-knob" />
            </div>
          </div>
        </div>
        <div class="prefs-card-footer">
          <button class="save-btn" @click="$emit('save', { notifications: prefsForm.notifications })">
            <i class="fas fa-check me-1"></i>Guardar
          </button>
        </div>
      </div>

      <!-- Privacidad -->
      <div class="prefs-card">
        <div class="prefs-card-head">
          <span class="prefs-card-icon-wrap prefs-card-icon-wrap--red">
            <i class="fas fa-user-shield"></i>
          </span>
          <span class="prefs-card-title">Privacidad</span>
        </div>
        <div class="prefs-card-body">
          <div class="toggle-row" @click="prefsForm.privacy.profile_visible = !prefsForm.privacy.profile_visible">
            <div class="toggle-left">
              <span class="toggle-icon-wrap toggle-icon-wrap--blue"><i class="fas fa-eye"></i></span>
              <div class="toggle-text">
                <span class="toggle-label">Perfil público</span>
                <span class="toggle-hint">Otros usuarios pueden ver tu perfil</span>
              </div>
            </div>
            <div :class="['toggle-switch', { 'toggle-switch--on': prefsForm.privacy.profile_visible }]">
              <div class="toggle-knob" />
            </div>
          </div>
          <div class="toggle-row" @click="prefsForm.privacy.show_email = !prefsForm.privacy.show_email">
            <div class="toggle-left">
              <span class="toggle-icon-wrap toggle-icon-wrap--amber"><i class="fas fa-envelope-open"></i></span>
              <div class="toggle-text">
                <span class="toggle-label">Mostrar email</span>
                <span class="toggle-hint">Tu correo aparece en tu perfil público</span>
              </div>
            </div>
            <div :class="['toggle-switch', { 'toggle-switch--on': prefsForm.privacy.show_email }]">
              <div class="toggle-knob" />
            </div>
          </div>
          <div class="toggle-row" @click="prefsForm.privacy.show_phone = !prefsForm.privacy.show_phone">
            <div class="toggle-left">
              <span class="toggle-icon-wrap toggle-icon-wrap--green"><i class="fas fa-phone"></i></span>
              <div class="toggle-text">
                <span class="toggle-label">Mostrar teléfono</span>
                <span class="toggle-hint">Tu número aparece en tu perfil público</span>
              </div>
            </div>
            <div :class="['toggle-switch', { 'toggle-switch--on': prefsForm.privacy.show_phone }]">
              <div class="toggle-knob" />
            </div>
          </div>
        </div>
        <div class="prefs-card-footer">
          <button class="save-btn" @click="$emit('save', { privacy: prefsForm.privacy })">
            <i class="fas fa-check me-1"></i>Guardar
          </button>
        </div>
      </div>

      <!-- Juego -->
      <div class="prefs-card">
        <div class="prefs-card-head">
          <span class="prefs-card-icon-wrap prefs-card-icon-wrap--purple">
            <i class="fas fa-gamepad"></i>
          </span>
          <span class="prefs-card-title">Configuración de Juego</span>
        </div>
        <div class="prefs-card-body">
          <div class="toggle-row" @click="prefsForm.game_settings.sound_enabled = !prefsForm.game_settings.sound_enabled">
            <div class="toggle-left">
              <span class="toggle-icon-wrap toggle-icon-wrap--amber"><i class="fas fa-volume-up"></i></span>
              <div class="toggle-text">
                <span class="toggle-label">Sonido</span>
                <span class="toggle-hint">Efectos de sonido durante el juego</span>
              </div>
            </div>
            <div :class="['toggle-switch', { 'toggle-switch--on': prefsForm.game_settings.sound_enabled }]">
              <div class="toggle-knob" />
            </div>
          </div>
          <div class="toggle-row" @click="prefsForm.game_settings.music_enabled = !prefsForm.game_settings.music_enabled">
            <div class="toggle-left">
              <span class="toggle-icon-wrap toggle-icon-wrap--purple"><i class="fas fa-music"></i></span>
              <div class="toggle-text">
                <span class="toggle-label">Música</span>
                <span class="toggle-hint">Música de fondo durante el juego</span>
              </div>
            </div>
            <div :class="['toggle-switch', { 'toggle-switch--on': prefsForm.game_settings.music_enabled }]">
              <div class="toggle-knob" />
            </div>
          </div>
          <div class="toggle-row" @click="prefsForm.game_settings.auto_dauber = !prefsForm.game_settings.auto_dauber">
            <div class="toggle-left">
              <span class="toggle-icon-wrap toggle-icon-wrap--blue"><i class="fas fa-magic"></i></span>
              <div class="toggle-text">
                <span class="toggle-label">Auto Dauber</span>
                <span class="toggle-hint">Marca automáticamente los números en tus cartones</span>
              </div>
            </div>
            <div :class="['toggle-switch', { 'toggle-switch--on': prefsForm.game_settings.auto_dauber }]">
              <div class="toggle-knob" />
            </div>
          </div>
          <div class="toggle-row" @click="prefsForm.game_settings.card_animation = !prefsForm.game_settings.card_animation">
            <div class="toggle-left">
              <span class="toggle-icon-wrap toggle-icon-wrap--green"><i class="fas fa-film"></i></span>
              <div class="toggle-text">
                <span class="toggle-label">Animaciones</span>
                <span class="toggle-hint">Efectos visuales en los cartones de bingo</span>
              </div>
            </div>
            <div :class="['toggle-switch', { 'toggle-switch--on': prefsForm.game_settings.card_animation }]">
              <div class="toggle-knob" />
            </div>
          </div>
        </div>
        <div class="prefs-card-footer">
          <button class="save-btn" @click="$emit('save', { game_settings: prefsForm.game_settings })">
            <i class="fas fa-check me-1"></i>Guardar
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ prefsForm: any }>()
defineEmits<{ save: [patch: Record<string, any>] }>()
</script>

<style scoped>
/* ── Grid 2×2 ── */
.prefs-tab  { }
.prefs-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

/* ── Card ── */
.prefs-card {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--ds-border);
    border-radius: 14px;
    overflow: hidden;
    background: var(--ds-surface);
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    transition: box-shadow 0.2s;
}
.prefs-card:hover {
    box-shadow: 0 4px 16px rgba(102,126,234,0.10);
}

/* ── Card head ── */
.prefs-card-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0.9rem 1.1rem;
    background: var(--ds-surface-2);
    border-bottom: 1px solid var(--ds-border);
}
.prefs-card-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--ds-text);
    letter-spacing: 0.01em;
}

/* ── Icon wrap variants ── */
.prefs-card-icon-wrap {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
}
.prefs-card-icon-wrap--blue   { background: #e8eaff; color: #667eea; }
.prefs-card-icon-wrap--amber  { background: #fff8e1; color: #f59e0b; }
.prefs-card-icon-wrap--red    { background: #fee2e2; color: #ef4444; }
.prefs-card-icon-wrap--purple { background: #f3e8ff; color: #8b5cf6; }

/* ── Card body ── */
.prefs-card-body {
    flex: 1;
    padding: 0.85rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: 0;
}

/* ── Card footer ── */
.prefs-card-footer {
    padding: 0.75rem 1.1rem;
    border-top: 1px solid var(--ds-border);
    background: var(--ds-surface-2);
}

/* ── Save button ── */
.save-btn {
    display: inline-flex;
    align-items: center;
    padding: 6px 16px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    border: none;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    cursor: pointer;
    transition: filter 0.15s, transform 0.15s;
    box-shadow: 0 2px 6px rgba(102,126,234,0.25);
}
.save-btn:hover {
    filter: brightness(1.08);
    transform: translateY(-1px);
}

/* ── Select fields ── */
.field-group { margin-bottom: 0.9rem; }
.field-group:last-child { margin-bottom: 0; }
.field-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--ds-text-2);
    margin-bottom: 5px;
}
.field-select-wrap {
    position: relative;
}
.field-select {
    width: 100%;
    padding: 0.45rem 2rem 0.45rem 0.75rem;
    border: 1px solid var(--ds-border-2);
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--ds-text);
    background: var(--ds-surface);
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.field-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}
.field-select-arrow {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.65rem;
    color: var(--ds-text-muted);
    pointer-events: none;
}

/* ── Toggle rows ── */
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--ds-border);
    cursor: pointer;
    user-select: none;
    transition: background 0.1s;
    border-radius: 6px;
}
.toggle-row:last-child { border-bottom: none; }
.toggle-row:hover { background: var(--ds-surface-2); padding-left: 4px; padding-right: 4px; }

.toggle-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 0;
}

/* ── Toggle icon variants ── */
.toggle-icon-wrap {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    flex-shrink: 0;
}
.toggle-icon-wrap--blue   { background: #e8eaff; color: #667eea; }
.toggle-icon-wrap--purple { background: #f3e8ff; color: #8b5cf6; }
.toggle-icon-wrap--green  { background: #d1fae5; color: #059669; }
.toggle-icon-wrap--amber  { background: #fff8e1; color: #f59e0b; }
.toggle-icon-wrap--red    { background: #fee2e2; color: #ef4444; }

.toggle-text {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}
.toggle-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--ds-text);
    line-height: 1.2;
}
.toggle-hint {
    font-size: 0.7rem;
    color: var(--ds-text-muted);
    line-height: 1.3;
}

/* ── Toggle switch ── */
.toggle-switch {
    width: 38px;
    height: 21px;
    border-radius: 21px;
    background: var(--ds-border-2);
    position: relative;
    flex-shrink: 0;
    transition: background 0.22s ease;
}
.toggle-switch--on {
    background: linear-gradient(135deg, #667eea, #764ba2);
}
.toggle-knob {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    transition: transform 0.22s ease;
}
.toggle-switch--on .toggle-knob {
    transform: translateX(17px);
}

/* ── Responsive ── */
@media (max-width: 720px) {
    .prefs-grid { grid-template-columns: 1fr; }
}
</style>
