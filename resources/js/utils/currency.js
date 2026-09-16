import { usePage } from '@inertiajs/vue3';

const LOCALE_TAGS = {
    en: 'en-IE',
    fr: 'fr-FR',
    de: 'de-DE',
    es: 'es-ES',
    it: 'it-IT',
};

export function useCurrency() {
    const page = usePage();

    return (amount) => {
        const localeTag = LOCALE_TAGS[page.props.locale] ?? 'en-IE';
        const currencyCode = page.props.currency?.code ?? 'EUR';

        return new Intl.NumberFormat(localeTag, {
            style: 'currency',
            currency: currencyCode,
        }).format(Number(amount));
    };
}
