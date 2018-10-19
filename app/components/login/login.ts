import {Component} from '@angular/core';
import {Router} from '@angular/router';
import {LoginCredentials} from "../../models/User";
import {ApiService} from "../../services/ApiService";
import {AuthService} from "../../services/AuthService";

@Component({
    templateUrl: 'login.html',
})
export class LoginComponent {
    public error: string;
    public submitting: boolean = false;

    public loginData: LoginCredentials = {
        email: '',
        password: '',
    };

    constructor(
        private authService: AuthService,
        private apiService: ApiService,
        private router: Router,
    ) {}

    signIn() {
        this.submitting = true;

        this.authService.logIn(this.loginData)
            .subscribe(
                resp => {
                    this.apiService.setAuth(resp.token, true);

                    if (resp.isMfaEnabled) {
                        this.router.navigate(['/confirm-mfa']);
                    } else {
                        this.apiService.defaultRedirect();
                    }
                },
                error => {this.error = error;},
            )
            .add(() => {this.submitting = false;});
    }
}
