import { usePage } from '@inertiajs/vue3';
import { trans } from './translations';

export function useTranslations() {
    const page = usePage();

    return (key) => trans(page.props.locale, key);
}
