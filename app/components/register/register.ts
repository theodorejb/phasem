import {Component} from '@angular/core';
import {Router} from '@angular/router';
import {AuthService} from "../../services/AuthService";
import {NewUser} from "../../models/User";

@Component({
    templateUrl: 'register.html',
})
export class RegisterComponent {
    public submitting: boolean = false;
    public error: string;

    public newUser: NewUser = {
        fullName: '',
        email: '',
        password: '',
    };

    constructor(
        private authService: AuthService,
        private router: Router
    ) {}

    register() {
        this.submitting = true;

        this.authService.createUser(this.newUser)
            .mergeMap(() => this.authService.logIn(this.newUser))
            .subscribe(
                () => {
                    this.router.navigate(['/']);
                },
                error => {this.error = error;}
            )
            .add(() => {this.submitting = false;});
    }
}
