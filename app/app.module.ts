import {NgModule} from '@angular/core';
import {BrowserModule, Title} from '@angular/platform-browser';
import {FormsModule} from '@angular/forms';
import {HttpClientModule} from '@angular/common/http';
import {RouterModule} from '@angular/router';
import {appComponents, appRoutes} from './routing';
import {AppComponent} from './components/app/app';

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
    bootstrap: [AppComponent],
})
export class AppModule {}
