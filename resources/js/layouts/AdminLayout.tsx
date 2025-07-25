import { Link } from '@kobalte/core/link';
import { GalleryHorizontalEnd, Layers, LayoutDashboard, NotebookText, Tags, Users } from 'lucide-solid';
import { Component, createSignal, JSXElement, Show, useContext } from 'solid-js';
import { AppContext } from '../App';
import ApplicationLogo from '../components/ApplicationLogo';
import AdminNavLink from '../components/ui/AdminNavLink';
import { locationIs } from '../util/Helpers';

const AdminLayout: Component<{ children?: JSXElement }> = (props) => {
	const { appState } = useContext(AppContext);
	const [sideBarOpen, setSideBarOpen] = createSignal(false);

	return (
		<Show when={appState.auth.user?.isAdmin}>
			<div class="fixed sm:relative w-full top-0 z-20 flex flex-row flex-wrap items-center bg-gray-900 py-4 px-8 border-b border-gray-700">
				<div class="flex-none w-56 flex flex-row items-center">
					<ApplicationLogo class="block h-8 w-auto text-blue-500 hover:text-blue-600" />
					<span class="ml-3 mr-1 text-white">{import.meta.env.VITE_APP_NAME}</span>

					<button
						onClick={() => setSideBarOpen(!sideBarOpen())}
						class="bg-gray-900 inline-flex items-center justify-center p-2 rounded-md text-gray-400 focus:outline-none focus:text-gray-500 transition duration-150 ease-in-out text-right block sm:hidden"
					>
						<svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
							<path class={(sideBarOpen() ? 'hidden' : 'inline-flex')} stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
							<path class={(sideBarOpen() ? 'inline-flex' : 'hidden')} stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
						</svg>
					</button>
				</div>
				<div class="flex flex-1 pl-3 flex-row flex-wrap items-center">
					<div class="text-gray-300 hover:text-white w-full flex flex-row justify-end">
						<Link href="/" class="transition duration-500 ease-in-out text-sm text-gray-300 hover:text-white">Home</Link>
					</div>
				</div>
			</div>
			<div class="min-h-screen flex flex-row flex-wrap">
				<div class={`${sideBarOpen() ? '-ml-0' : '-ml-64'} fixed flex flex-col flex-wrap bg-gray-900 border-r border-gray-700 pt-6 pl-6 flex-none w-60 sm:ml-0 sm:relative top-0 sm:top-auto z-30 sm:z-0 min-h-screen shadow-xl sm:shadow-none duration-150 ease-in-out animated`}>
					<div class="flex flex-col">
						<div class="text-right block sm:hidden mb-4">
							<button onClick={() => setSideBarOpen(!sideBarOpen())} class="bg-gray-900 inline-flex items-center justify-center p-2 rounded-md text-gray-400 focus:outline-none focus:text-gray-500 transition duration-150 ease-in-out text-right block sm:hidden">
								<svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
									<path class={(sideBarOpen() ? 'hidden' : 'inline-flex')} stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
									<path class={(sideBarOpen() ? 'inline-flex' : 'hidden')} stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
								</svg>
							</button>
						</div>
						<p class="uppercase text-md text-white mb-5 tracking-wider">Administration</p>
						<AdminNavLink href="/admin/dashboard" active={locationIs('admin') || locationIs('admin.dashboard')}>
							<div class="flex flex-row justify-start items-center">
								<LayoutDashboard size={20} class="mr-1" />
								<div>Dashboard</div>
							</div>
						</AdminNavLink>
						<AdminNavLink href="/admin/cards" active={locationIs('admin.cards')}>
							<div class="flex flex-row justify-start items-center">
								<GalleryHorizontalEnd size={20} class="mr-1" />
								<div>Cards</div>
							</div>
						</AdminNavLink>
						<AdminNavLink href="/admin/decks" active={locationIs('admin.decks')}>
							<div class="flex flex-row justify-start items-center">
								<Layers size={20} class="mr-1" />
								<div>Decks</div>
							</div>
						</AdminNavLink>
						<AdminNavLink href="/admin/tags" active={locationIs('admin.tags')}>
							<div class="flex flex-row justify-start items-center">
								<Tags size={20} class="mr-1" />
								<div>Tags</div>
							</div>
						</AdminNavLink>
						<AdminNavLink href="/admin/pages" active={locationIs('admin.pages') || locationIs('admin.pages.new') || locationIs('admin.pages.:id')}>
							<div class="flex flex-row justify-start items-center">
								<NotebookText size={20} class="mr-1" />
								<div>Pages</div>
							</div>
						</AdminNavLink>
						<AdminNavLink href="/admin/users" active={locationIs('admin.users')}>
							<div class="flex flex-row justify-start items-center">
								<Users size={20} class="mr-1" />
								<div>Users</div>
							</div>
						</AdminNavLink>
					</div>
				</div>
				<div class="w-full admin-viewport p-6 mt-16 md:mt-0">
					{ props.children }
				</div>
			</div>
		</Show>
	);
};

export default AdminLayout;
