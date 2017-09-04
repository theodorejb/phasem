import {Component} from '@angular/core';
import {Router} from '@angular/router';
import {AuthService} from "../../services/AuthService";
import {LoginCredentials} from "../../models/User";

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
        private router: Router
    ) {}

    signIn() {
        this.submitting = true;

        this.authService.logIn(this.loginData)
            .subscribe(
                () => {
                    this.router.navigate(['/']);
                },
                error => {this.error = error;}
            )
            .add(() => {this.submitting = false;});
    }
}
