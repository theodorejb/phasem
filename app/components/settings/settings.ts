import {Component} from '@angular/core';

@Component({
    template: `<h2>Settings</h2>
        <nav class="tabs">
            <ul>
                <li routerLinkActive="selected" [routerLinkActiveOptions]="{exact: true}"><a routerLink="/settings">Account</a></li>
                <li routerLinkActive="selected"><a routerLink="/settings/security">Security</a></li>
            </ul>
        </nav>
        <router-outlet></router-outlet>`,
})
export class SettingsComponent {
}
