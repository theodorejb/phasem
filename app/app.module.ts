import {NgModule} from '@angular/core';
import {BrowserModule, Title} from '@angular/platform-browser';
import {FormsModule} from '@angular/forms';
import {HttpClientModule} from '@angular/common/http';
import {RouterModule} from '@angular/router';
import {appComponents, appRoutes} from './routing';
import {ApiService} from './services/ApiService';
import {AppComponent} from './components/app/app';
import {AuthService} from './services/AuthService';
import {AuthGuard} from "./services/AuthGuard";
import {NoAuthGuard} from "./services/NoAuthGuard";

@NgModule({
    imports: [
        BrowserModule,
        FormsModule,
        HttpClientModule,
        RouterModule.forRoot(appRoutes),
    ],
    declarations: [
        AppComponent,
        ...appComponents,
    ],
    providers: [
        ApiService,
        AuthGuard,
        AuthService,
        NoAuthGuard,
        Title,
    ],
    bootstrap: [AppComponent],
})
export class AppModule {}
