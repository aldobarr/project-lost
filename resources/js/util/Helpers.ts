import { Location, useLocation, useParams } from '@solidjs/router';
import ApiResponse from '../interfaces/api/ApiResponse';

const locationIs = (path: string) => {
	const params = useParams();
	const pathParts = path.split('.');

	const location: Location<unknown> = useLocation();
	const locations: string[] = location.pathname.split('/');
	if (locations.length <= 1) {
		return path === '';
	}

	if (pathParts.length !== (locations.length - 1)) {
		return false;
	}

	for (let i = 0; i < pathParts.length; i++) {
		if ((i + 1) >= locations.length) {
			return false;
		}

		if (pathParts[i].startsWith(':') && params[pathParts[i].substring(1)] === undefined) {
			return false;
		} else if (pathParts[i] !== locations[(i + 1)]) {
			return false;
		}
	}

	return true;
};

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const getPageQuery = (api: ApiResponse<any>) => {
	if (!api.links || !api.meta) {
		return '';
	}

	const parts = api.links.first.split('?');
	if (parts.length <= 1) {
		return '';
	}

	const params = new URLSearchParams(parts[1]);
	const filteredParams = Object.fromEntries(params.entries().filter(val => val[0] !== 'page'));
	if (api.meta.current_page > 1) {
		filteredParams.page = `${api.meta.current_page}`;
	}

	return '?' + (new URLSearchParams(filteredParams)).toString();
};

export { locationIs, getPageQuery };
