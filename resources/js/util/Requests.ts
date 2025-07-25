import { Navigator } from '@solidjs/router';
import { SetStoreFunction } from 'solid-js/store';
import AppState from '../interfaces/AppState';

export class ApiRequest {
	private static instance: ApiRequest;
	private baseUrl: string = '';
	private apiEndpoint: string = '';
	private appState: AppState;
	private setAppState: SetStoreFunction<AppState>;
	private navigate: Navigator;

	private constructor(appState: AppState, setAppState: SetStoreFunction<AppState>, navigate: Navigator) {
		this.appState = appState;
		this.setAppState = setAppState;
		this.navigate = navigate;
		this.parseBaseUrl(import.meta.env.VITE_API_URL);
	}

	private parseBaseUrl(baseUrl: string): void {
		const url = new URL(baseUrl);
		this.baseUrl = url.origin;
		this.apiEndpoint = url.pathname;
	}

	static initialize(appState: AppState, setAppState: SetStoreFunction<AppState>, navigate: Navigator): ApiRequest {
		if (!ApiRequest.instance) {
			ApiRequest.instance = new ApiRequest(appState, setAppState, navigate);
		}

		return ApiRequest.instance;
	}

	static getInstance(): ApiRequest {
		if (!ApiRequest.instance) {
			throw new Error('Application is not initialized.');
		}

		return ApiRequest.instance;
	}

	makeRequest(endpoint: string, init?: RequestInit, isFormData: boolean = false): Promise<Response> {
		init = init ?? {};
		const headers = new Headers({ Accept: 'application/json' });

		if (!isFormData) {
			headers.set('Content-Type', 'application/json');
		}

		(new Headers(init.headers ?? {})).forEach((value, key) => headers.set(key, value));

		if (this.appState.auth.token) {
			headers.set('Authorization', `Bearer ${this.appState.auth.token}`);
		}

		init.headers = headers;
		if (endpoint.startsWith('/')) {
			endpoint = endpoint.slice(1);
		}

		const url = endpoint.startsWith('http') ? (new URL(endpoint)) : (new URL(`${this.apiEndpoint}/${endpoint}`, this.baseUrl));
		return new Promise<Response>((resolve, reject) => {
			fetch(url, init)
				.then((response) => {
					if (response.status === 401) {
						this.setAppState('auth', { token: null, user: null });
						this.navigate('/login', { replace: true });
						reject(new Error('Unauthenticated!'));
						return;
					}

					resolve(response);
				}).catch(error => reject(error));
		});
	}
}

const request = (endpoint: string, init?: RequestInit, isFormData: boolean = false) => {
	return ApiRequest.getInstance().makeRequest(endpoint, init, isFormData);
};

export default request;
