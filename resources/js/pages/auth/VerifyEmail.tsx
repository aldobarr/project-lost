import { Component, createEffect, createSignal, on, onMount, Show, useContext } from 'solid-js';
import { useNavigate, useParams } from '@solidjs/router';
import { Alert } from '@kobalte/core/alert';
import { AppContext } from '../../App';
import { produce } from 'solid-js/store';
import Spinner from '../../components/ui/Spinner';
import Label from '../../components/ui/Label';
import { Input } from '../../components/ui/Input';
import Checkbox from '../../components/ui/Checkbox';
import Button from '../../components/ui/Button';
import ValidationErrors from '../../components/ui/ValidationErrors';
import Authentication from '../../interfaces/Authentication';
import { validatePasswordFields } from '../../util/AuthHelpers';
import request from '../../util/Requests';

const VerifyEmail: Component = () => {
	const params = useParams();
	const navigate = useNavigate();
	const [registrationToken, setRegistrationToken] = createSignal<string | null>(null);
	const [noEmail, setNoEmail] = createSignal(false);
	const { appState, setAppState } = useContext(AppContext);
	const goBack = () => setTimeout(() => navigate('/register', { replace: true }), 2000);

	const [status, setStatus] = createSignal<string | null>(null);
	const [name, setName] = createSignal('');
	const [password, setPassword] = createSignal('');
	const [passwordConfirmation, setPasswordConfirmation] = createSignal('');
	const [remember, setRemember] = createSignal(false);
	const [errors, setErrors] = createSignal<string[]>([]);
	const [processing, setProcessing] = createSignal(false);

	onMount(async () => {
		const token = params.token;

		if (!appState.validatingEmail) {
			setNoEmail(true);
			setAppState(produce((appState) => {
				delete appState.validatingEmail;
			}));

			goBack();
			return;
		}

		try {
			const res = await request('/verify/email/token', {
				method: 'POST',
				body: JSON.stringify({ token: token, email: appState.validatingEmail }),
			});

			const response = await res.json();
			if (!response.success) {
				throw new Error((Object.values(response.errors || {}) as string[][]).flat().join(', '));
			}

			setRegistrationToken(response.data.token);
		} catch (error) {
			console.error('Error verifying email:', error);
			setAppState(produce((appState) => {
				delete appState.validatingEmail;
			}));

			setNoEmail(true);
			goBack();
		}
	});

	const reset = (processing: boolean = false) => {
		setStatus(null);
		setErrors([]);
		setProcessing(processing);
	};

	const submit = async (e: SubmitEvent) => {
		e.preventDefault();

		let errors: string[] = [];
		if (name().trim() === '') {
			errors.push('The name field may not be blank.');
		}

		errors = errors.concat(validatePasswordFields(password(), passwordConfirmation()));

		if (Object.keys(errors).length > 0) {
			setErrors(errors);
			return;
		}

		reset(true);
	};

	createEffect(on(processing, async (isProcessing) => {
		if (!isProcessing) {
			return;
		}

		const redirect = (data: Authentication) => {
			setAppState('auth', data);
			setTimeout(() => navigate('/', { replace: true }), 1500);
		};

		try {
			const body: {
				name: string;
				email: string;
				password: string;
				password_confirmation: string;
				token: string | null;
				remember?: boolean;
			} = {
				name: name().trim(),
				email: appState.validatingEmail ?? '',
				password: password(),
				password_confirmation: passwordConfirmation(),
				token: registrationToken(),
			};

			if (remember()) {
				body['remember'] = true;
			}

			const response = await request('/register', {
				method: 'POST',
				body: JSON.stringify(body),
			});

			const data = await response.json();
			if (!data.success) {
				setErrors((Object.values(data.errors || {}) as string[][]).flat());
				setStatus(null);
				return;
			}

			setErrors([]);
			setStatus('Success! You will be redirected shortly.');
			redirect(data.data as Authentication);
		} catch (error) {
			console.error(error);
			setErrors(['An unknown error occurred.']);
			setStatus(null);
		} finally {
			setProcessing(false);
		}
	}));

	return (
		<>
			<section class="text-gray-400 body-font">
				<div class="container px-5 py-24 mx-auto flex flex-wrap items-center">
					<div class="md:w-1/2 bg-gray-900 bg-opacity-50 rounded-lg p-8 flex flex-col md:mx-auto w-full mt-10 md:mt-0">
						<Show when={registrationToken() !== null}>
							<Show when={status() != null}>
								<Alert class="alert alert-success">{status()}</Alert>
							</Show>
							<ValidationErrors errors={errors} />

							<form onSubmit={submit}>
								<div class="relative mb-4">
									<Label for="name" class="leading-7 text-sm text-gray-400" value="Name" />
									<Input
										type="text"
										name="name"
										value={name()}
										class="mt-1 block w-full"
										autoComplete="username"
										handleChange={e => setName(e.currentTarget.value)}
										required
										darkBg
									/>
								</div>
								<div class="relative mb-4">
									<Label for="password" class="leading-7 text-sm text-gray-400" value="Password" />
									<Input
										type="password"
										name="password"
										value={password()}
										class="mt-1 block w-full"
										handleChange={e => setPassword(e.currentTarget.value)}
										required
										darkBg
									/>
								</div>
								<div class="relative mb-4">
									<Label for="password" class="leading-7 text-sm text-gray-400" value="Password" />
									<Input
										type="password"
										name="password"
										value={passwordConfirmation()}
										class="mt-1 block w-full"
										handleChange={e => setPasswordConfirmation(e.currentTarget.value)}
										required
										darkBg
									/>
								</div>
								<div class="relative mb-4">
									<label class="flex items-center">
										<Checkbox name="remember" checked={remember()} handleChange={e => setRemember(e.target.checked)} />

										<span class="ml-2 text-sm text-gray-400">Remember me</span>
									</label>
								</div>
								<div class="flex items-center justify-end mt-4">
									<Button class="ml-4" processing={processing}>
										Register
									</Button>
								</div>
							</form>
						</Show>

						<Show when={registrationToken() === null}>
							<Show when={noEmail()}>
								<Alert class="alert alert-danger">
									<div>Your email address could not be verified. Please check the link you clicked or try again later.</div>
									<div>You will be redirected back to the registration page.</div>
								</Alert>
							</Show>
							<div class="flex flex-row w-full items-start">
								<div>Validating your email address</div>
								<Spinner />
							</div>
						</Show>
					</div>
				</div>
			</section>
		</>
	);
};

export default VerifyEmail;
