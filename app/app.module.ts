import {NgModule} from '@angular/core';
import {BrowserModule, Title} from '@angular/platform-browser';
import {FormsModule} from '@angular/forms';
import {HttpClientModule} from '@angular/common/http';
import {RouterModule} from '@angular/router';

// Observable class extensions
import 'rxjs/add/observable/of';
import 'rxjs/add/observable/throw';
//import 'rxjs/add/observable/forkjoin';

// Observable operators
import 'rxjs/add/operator/catch';
import 'rxjs/add/operator/filter';
import 'rxjs/add/operator/map';
import 'rxjs/add/operator/mergeMap';
import 'rxjs/add/operator/publishReplay';

import {appRoutes, appComponents} from './routing';

import {ApiService} from './services/ApiService';
import {AppComponent} from './components/app/app';
import {AuthService} from './services/AuthService';

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
        AuthService,
        Title
    ],
    bootstrap: [AppComponent]
})
export class AppModule {}
