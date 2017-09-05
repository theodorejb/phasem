import {Routes} from '@angular/router';
import {PageNotFoundComponent} from './components/app/page-not-found';
import {LoginComponent} from './components/login/login';
import {RegisterComponent} from "./components/register/register";
import {HomeComponent} from "./components/home/home";
import {LogOutComponent} from "./components/logout/logout";
import {SettingsComponent} from "./components/settings/settings";
import {AuthGuard} from "./services/AuthGuard";
import {NoAuthGuard} from "./services/NoAuthGuard";

export const appRoutes: Routes = [
    {path: '', pathMatch: 'full', component: HomeComponent},
    {path: 'login', component: LoginComponent, canActivate: [NoAuthGuard], data: {title: 'Sign in'}},
    {path: 'register', component: RegisterComponent, canActivate: [NoAuthGuard], data: {title: 'Create account'}},
    {path: 'logout', component: LogOutComponent, data: {title: 'Sign out'}},
    {path: 'settings', component: SettingsComponent, canActivate: [AuthGuard], data: {title: 'Account settings'}},
    {path: '**', component: PageNotFoundComponent, data: {title: '404'}},
];

export const appComponents = [
    HomeComponent,
    LoginComponent,
    LogOutComponent,
    PageNotFoundComponent,
    RegisterComponent,
    SettingsComponent,
];
