import {Routes} from '@angular/router';
import {PageNotFoundComponent} from './components/app/page-not-found';
import {ConfirmMfaComponent} from "./components/confirm-mfa/confirm-mfa";
import {HomeComponent} from "./components/home/home";
import {LoginComponent} from './components/login/login';
import {LogOutComponent} from "./components/logout/logout";
import {RegisterComponent} from "./components/register/register";
import {settingComponents, settingRoutes} from "./components/settings/routes";
import {SettingsComponent} from "./components/settings/settings";
import {AuthGuard} from "./services/AuthGuard";
import {LoggedInGuard} from "./services/LoggedInGuard";
import {NoAuthGuard} from "./services/NoAuthGuard";

export const appRoutes: Routes = [
    {path: '', pathMatch: 'full', component: HomeComponent},
    {path: 'login', component: LoginComponent, canActivate: [NoAuthGuard], data: {title: 'Sign in'}},
    {path: 'confirm-mfa', component: ConfirmMfaComponent, canActivate: [AuthGuard], data: {title: 'Verify two-factor authentication'}},
    {path: 'register', component: RegisterComponent, canActivate: [NoAuthGuard], data: {title: 'Create account'}},
    {path: 'logout', component: LogOutComponent, data: {title: 'Sign out'}},
    {path: 'settings', component: SettingsComponent, canActivate: [LoggedInGuard], children: settingRoutes},
    {path: '**', component: PageNotFoundComponent, data: {title: '404'}},
];

export const appComponents = [
    ConfirmMfaComponent,
    HomeComponent,
    LoginComponent,
    LogOutComponent,
    PageNotFoundComponent,
    RegisterComponent,
    SettingsComponent,
    ...settingComponents,
];
