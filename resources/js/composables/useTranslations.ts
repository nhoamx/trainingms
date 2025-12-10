import { usePage, router } from '@inertiajs/vue3'
import { computed } from 'vue'

/**
 * Composable for handling translations in Vue components.
 * Uses translations shared from Laravel via Inertia.
 */
export function useTranslations() {
  const page = usePage()

  /**
   * Current locale from Laravel
   */
  const locale = computed(() => page.props.locale || 'es')

  /**
   * All translations for current locale
   */
  const translations = computed(() => page.props.translations || {})

  /**
   * Translate a key to the current locale.
   * Falls back to the key itself if translation is not found.
   *
   * @param key - The translation key
   * @param replacements - Optional replacements for placeholders like :name
   */
  function t(key: string, replacements: Record<string, string | number> = {}): string {
    let translation = translations.value[key] || key

    // Handle replacements (e.g., :name, :count)
    Object.entries(replacements).forEach(([placeholder, value]) => {
      translation = translation.replace(`:${placeholder}`, String(value))
    })

    return translation
  }

  /**
   * Check if a translation key exists
   */
  function has(key: string): boolean {
    return key in translations.value
  }

  /**
   * Switch the application locale
   * This will reload the page with the new locale
   */
  function setLocale(newLocale: string): void {
    router.visit(window.location.pathname, {
      data: { lang: newLocale },
      preserveState: false,
      preserveScroll: true,
    })
  }

  return {
    locale,
    translations,
    t,
    has,
    setLocale,
  }
}
