from locust import HttpUser, task, between
import random

class WebsiteUser(HttpUser):
    wait_time = between(1, 2)

    def random_ip(self):
        return f"192.168.1.{random.randint(1,254)}"

    @task
    def login(self):
        headers = {
            "X-Forwarded-For": self.random_ip()
        }

        self.client.post(
            "/login.php",
            data={
                "username": "test",
                "password": "test"
            },
            headers=headers
        )