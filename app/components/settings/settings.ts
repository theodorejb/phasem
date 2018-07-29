import {Component} from '@angular/core';
import {Router} from '@angular/router';
import {AuthService} from "../../services/AuthService";
import {ApiService} from "../../services/ApiService";

@Component({
    templateUrl: 'settings.html',
})
export class SettingsComponent {
    public error: string;
    public submitting: boolean = false;

    constructor(
        private authService: AuthService,
        private apiService: ApiService,
        private router: Router,
    ) {}
}
