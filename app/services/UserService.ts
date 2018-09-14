import {Injectable} from '@angular/core';
import {PasswordChange, UserEmail, UserProfile} from '../models/User';
import {ApiService} from "./ApiService";

@Injectable({
    providedIn: 'root',
})
export class UserService {
    constructor(private api: ApiService) {}

    updateProfile(profile: UserProfile) {
        return this.api.requestBody('post', 'me/profile', profile);
    }

    updateEmail(userEmail: UserEmail) {
        return this.api.requestBody('post', 'me/email', userEmail);
    }

    changePassword(passwordChange: PasswordChange) {
        return this.api.requestBody('post', 'me/password', passwordChange);
    }
}
